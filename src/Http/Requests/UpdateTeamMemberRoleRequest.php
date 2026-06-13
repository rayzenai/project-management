<?php

namespace RayzenAI\ProjectManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use RayzenAI\ProjectManagement\Models\Member;
use RayzenAI\ProjectManagement\Models\Team;
use RayzenAI\ProjectManagement\Support\WorkspaceAccess;

class UpdateTeamMemberRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $team = $this->route('team');
        $member = $this->route('member');

        return $team instanceof Team
            && $member instanceof Member
            && WorkspaceAccess::canManageRosterOf($this->user(), $team)
            && WorkspaceAccess::canManageMember($this->user(), $member);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(['member', 'leader'])],
        ];
    }
}
