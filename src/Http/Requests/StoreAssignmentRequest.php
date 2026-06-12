<?php

namespace RayzenAI\ProjectManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['nullable', 'string', 'max:128'],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'personal_progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'personal_due_at' => ['nullable', 'date'],
            'personal_status_note' => ['nullable', 'string'],
        ];
    }
}
