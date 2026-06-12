<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RayzenAI\ProjectManagement\Http\Requests\StoreMemberRequest;
use RayzenAI\ProjectManagement\Http\Requests\UpdateMemberRequest;
use RayzenAI\ProjectManagement\Models\Member;

/**
 * A member optionally carries a login: providing a password (on create or
 * later via edit) provisions a host user with the member's email, edits keep
 * the two in sync, and deleting a member removes its login too.
 */
class MemberController extends Controller
{
    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $withLogin = $request->filled('password');

        DB::transaction(function () use ($request, $withLogin): void {
            $userId = null;

            if ($withLogin) {
                $userId = config('project-management.user_model')::create([
                    'name' => $request->validated('name'),
                    'email' => $request->validated('email'),
                    'password' => Hash::make($request->validated('password')),
                ])->getKey();
            }

            $member = Member::create([
                'user_id' => $userId,
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'title' => $request->validated('title'),
            ]);

            $member->teams()->sync($request->validated('team_ids', []));
        });

        return back()->with('workspace_flash', [
            'success' => true,
            'message' => $withLogin ? 'Member added — they can sign in now.' : 'Member added.',
        ]);
    }

    public function update(UpdateMemberRequest $request, Member $member): RedirectResponse
    {
        if (! $member->user_id && $request->filled('password') && ! ($request->validated('email') ?? $member->email)) {
            return back()->withErrors(['email' => 'An email is required to create a login.']);
        }

        DB::transaction(function () use ($request, $member): void {
            $member->update($request->safe()->except(['team_ids', 'password']));

            if ($request->has('team_ids')) {
                $member->teams()->sync($request->validated('team_ids', []));
            }

            $user = $member->user;

            if (! $user && $request->filled('password')) {
                $user = config('project-management.user_model')::create([
                    'name' => $member->name,
                    'email' => $member->email,
                    'password' => Hash::make($request->validated('password')),
                ]);

                $member->user_id = $user->getKey();
                $member->save();

                return;
            }

            if ($user) {
                $user->name = $member->name;
                if ($member->email) {
                    $user->email = $member->email;
                }
                if ($request->filled('password')) {
                    $user->password = Hash::make($request->validated('password'));
                }
                $user->save();
            }
        });

        return back()->with('workspace_flash', ['success' => true, 'message' => 'Member updated.']);
    }

    public function destroy(Member $member): RedirectResponse
    {
        DB::transaction(function () use ($member): void {
            $user = $member->user;
            $member->delete();
            $user?->delete();
        });

        return back()->with('workspace_flash', ['success' => true, 'message' => 'Member and their login removed.']);
    }
}
