<?php

namespace RayzenAI\ProjectManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use RayzenAI\ProjectManagement\Models\Team;
use RayzenAI\ProjectManagement\Support\WorkspaceAccess;

/**
 * Attaches a member to a team. Either `member_id` (attach an existing member)
 * or `name` (create a brand-new member, optionally with a login) is required.
 */
class StoreTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $team = $this->route('team');

        return $team instanceof Team
            && WorkspaceAccess::canManageRosterOf($this->user(), $team);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'member_id' => ['required_without:name', 'nullable', 'integer', 'exists:members,id'],
            'name' => ['required_without:member_id', 'nullable', 'string', 'max:255'],
            'email' => ['nullable', 'required_with:password', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8'],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
