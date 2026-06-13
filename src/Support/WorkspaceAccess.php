<?php

namespace RayzenAI\ProjectManagement\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use RayzenAI\ProjectManagement\Models\Member;
use RayzenAI\ProjectManagement\Models\Project;
use RayzenAI\ProjectManagement\Models\Team;

/**
 * The single source of truth for workspace authorization. Super-admin is the
 * config-driven `manage-workspace` Gate; team-leadership is the `role` column
 * on the member_team pivot. Every controller, form request, and resource that
 * makes a role decision goes through here.
 */
class WorkspaceAccess
{
    public static function isSuperAdmin(?Authenticatable $user): bool
    {
        return $user !== null && Gate::forUser($user)->allows('manage-workspace');
    }

    public static function leadsTeam(?Authenticatable $user, Team $team): bool
    {
        return in_array($team->getKey(), self::ledTeamIds($user), true);
    }

    public static function canManageRosterOf(?Authenticatable $user, Team $team): bool
    {
        return self::isSuperAdmin($user) || self::leadsTeam($user, $team);
    }

    public static function canArchiveProject(?Authenticatable $user, Project $project): bool
    {
        if (self::isSuperAdmin($user)) {
            return true;
        }

        $ledTeamIds = self::ledTeamIds($user);

        return $ledTeamIds !== []
            && $project->teams()->whereIn('teams.id', $ledTeamIds)->exists();
    }

    /**
     * True when the user may create a member and attach it to the given teams:
     * super-admins anywhere, leaders only for teams they all lead.
     *
     * @param  list<int>  $teamIds
     */
    public static function canCreateMemberForTeams(?Authenticatable $user, array $teamIds): bool
    {
        if (self::isSuperAdmin($user)) {
            return true;
        }

        if ($teamIds === []) {
            return false;
        }

        $ledTeamIds = self::ledTeamIds($user);

        foreach ($teamIds as $teamId) {
            if (! in_array((int) $teamId, $ledTeamIds, true)) {
                return false;
            }
        }

        return true;
    }

    public static function canManageMember(?Authenticatable $user, Member $member): bool
    {
        if (self::isSuperAdmin($user)) {
            return true;
        }

        $ledTeamIds = self::ledTeamIds($user);

        return $ledTeamIds !== []
            && $member->teams()->whereIn('teams.id', $ledTeamIds)->exists();
    }

    /**
     * The team ids the user's linked member leads. Does not create a member as
     * a side effect (unlike Member::forUser), so it is safe in authorization.
     *
     * @return list<int>
     */
    public static function ledTeamIds(?Authenticatable $user): array
    {
        if ($user === null) {
            return [];
        }

        $member = Member::query()->where('user_id', $user->getAuthIdentifier())->first();

        if ($member === null) {
            return [];
        }

        return $member->ledTeams()->pluck('teams.id')->map(fn ($id): int => (int) $id)->all();
    }
}
