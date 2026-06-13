<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RayzenAI\ProjectManagement\Http\Requests\StoreMemberRequest;
use RayzenAI\ProjectManagement\Http\Requests\UpdateMemberRequest;
use RayzenAI\ProjectManagement\Http\Resources\MemberResource;
use RayzenAI\ProjectManagement\Models\Member;
use RayzenAI\ProjectManagement\Support\WorkspaceAccess;

/**
 * JSON sibling of the Workspace\MemberController. Reuses the same FormRequests
 * (authorization) and mirrors its login-provisioning mutation logic, differing
 * only in the response shape.
 *
 * A member optionally carries a login: providing a password (on create or
 * later via edit) provisions a host user with the member's email, edits keep
 * the two in sync, and deleting a member removes its login too.
 */
class MemberController extends Controller
{
    public function store(StoreMemberRequest $request): JsonResponse
    {
        $withLogin = $request->filled('password');

        $member = DB::transaction(function () use ($request, $withLogin): Member {
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

            return $member;
        });

        return response()->json([
            'message' => $withLogin ? 'Member added — they can sign in now.' : 'Member added.',
            'data' => new MemberResource($member->load('teams:id')),
        ], 201);
    }

    public function update(UpdateMemberRequest $request, Member $member): JsonResponse
    {
        if (! $member->user_id && $request->filled('password') && ! ($request->validated('email') ?? $member->email)) {
            return response()->json([
                'message' => 'An email is required to create a login.',
                'errors' => ['email' => 'An email is required to create a login.'],
            ], 422);
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

        return response()->json([
            'message' => 'Member updated.',
            'data' => new MemberResource($member->fresh()->load('teams:id')),
        ]);
    }

    public function destroy(Request $request, Member $member): JsonResponse
    {
        abort_unless(WorkspaceAccess::isSuperAdmin($request->user()), 403);

        DB::transaction(function () use ($member): void {
            $user = $member->user;
            $member->delete();
            $user?->delete();
        });

        return response()->json(['message' => 'Member and their login removed.']);
    }
}
