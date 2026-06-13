<?php

namespace RayzenAI\ProjectManagement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RayzenAI\ProjectManagement\Http\Requests\StoreTeamMemberRequest;
use RayzenAI\ProjectManagement\Http\Requests\UpdateTeamMemberRoleRequest;
use RayzenAI\ProjectManagement\Http\Resources\MemberResource;
use RayzenAI\ProjectManagement\Models\Member;
use RayzenAI\ProjectManagement\Models\Team;
use RayzenAI\ProjectManagement\Support\WorkspaceAccess;

/**
 * JSON sibling of Workspace\TeamMemberController — team-scoped roster management.
 * Authorization is per-team (canManageRosterOf via the FormRequest + the destroy
 * guard), so leaders touch only the teams they lead while super-admins manage all.
 */
class TeamMemberController extends Controller
{
    public function store(StoreTeamMemberRequest $request, Team $team): JsonResponse
    {
        $member = DB::transaction(function () use ($request, $team): Member {
            $memberId = $request->integer('member_id');

            if ($memberId) {
                $team->members()->syncWithoutDetaching([$memberId]);

                return Member::findOrFail($memberId);
            }

            $userId = null;

            if ($request->filled('password')) {
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

            $team->members()->syncWithoutDetaching([$member->id]);

            return $member;
        });

        return response()->json([
            'message' => 'Member added to team.',
            'data' => new MemberResource($member),
        ], 201);
    }

    public function destroy(Request $request, Team $team, Member $member): JsonResponse
    {
        abort_unless(WorkspaceAccess::canManageRosterOf($request->user(), $team), 403);

        $team->members()->detach($member->id);

        return response()->json(['message' => 'Member removed from team.']);
    }

    public function updateRole(UpdateTeamMemberRoleRequest $request, Team $team, Member $member): JsonResponse
    {
        $team->members()->updateExistingPivot($member->id, ['role' => $request->validated('role')]);

        return response()->json(['message' => 'Team role updated.']);
    }
}
