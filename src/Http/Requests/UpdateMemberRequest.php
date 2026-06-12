<?php

namespace RayzenAI\ProjectManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use RayzenAI\ProjectManagement\Models\Member;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /**
     * Name/email changes sync to the linked login; a password value resets it
     * (or provisions a login for a pre-login-era member).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $member = $this->route('member');
        $linkedUserId = $member instanceof Member ? $member->user_id : null;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($linkedUserId)],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'team_ids' => ['sometimes', 'array'],
            'team_ids.*' => ['integer', 'exists:teams,id'],
        ];
    }
}
