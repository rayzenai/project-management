<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Workspace;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use RayzenAI\ProjectManagement\Http\Requests\StoreMemberRequest;
use RayzenAI\ProjectManagement\Http\Requests\UpdateMemberRequest;
use RayzenAI\ProjectManagement\Models\Member;

class MemberController extends Controller
{
    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $member = Member::create($request->safe()->except('team_ids'));

        $member->teams()->sync($request->validated('team_ids', []));

        return back()->with('workspace_flash', ['success' => true, 'message' => 'Member added.']);
    }

    public function update(UpdateMemberRequest $request, Member $member): RedirectResponse
    {
        $member->update($request->safe()->except('team_ids'));

        if ($request->has('team_ids')) {
            $member->teams()->sync($request->validated('team_ids', []));
        }

        return back()->with('workspace_flash', ['success' => true, 'message' => 'Member updated.']);
    }

    public function destroy(Member $member): RedirectResponse
    {
        $member->delete();

        return back()->with('workspace_flash', ['success' => true, 'message' => 'Member removed.']);
    }
}
