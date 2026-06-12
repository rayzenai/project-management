<?php

namespace RayzenAI\ProjectManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use RayzenAI\ProjectManagement\Models\ProjectNote;

class StoreNoteRequest extends FormRequest
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
            'body' => ['required', 'string'],
            'type' => ['nullable', 'string', 'in:'.implode(',', array_keys(ProjectNote::TYPES))],
            'happened_at' => ['nullable', 'date'],
        ];
    }
}
