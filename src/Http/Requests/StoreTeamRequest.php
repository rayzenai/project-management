<?php

namespace RayzenAI\ProjectManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use RayzenAI\ProjectManagement\Support\WorkspaceAccess;

class StoreTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return WorkspaceAccess::isSuperAdmin($this->user());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:16'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:members,id'],
        ];
    }
}
