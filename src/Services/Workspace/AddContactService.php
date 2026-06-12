<?php

namespace RayzenAI\ProjectManagement\Services\Workspace;

use RayzenAI\ProjectManagement\Models\ProjectContact;
use RayzenAI\ProjectManagement\Models\Task;
use RayzenAI\ProjectManagement\Support\ServiceResult;
use Throwable;

class AddContactService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Task $task, int $userId, array $attributes): ServiceResult
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        if ($name === '') {
            return ServiceResult::failure('Contact name is required.', 422);
        }

        try {
            $contact = ProjectContact::create([
                'task_id' => $task->id,
                'user_id' => $userId,
                'name' => $name,
                'role' => $attributes['role'] ?? null,
                'email' => $attributes['email'] ?? null,
                'phone' => $attributes['phone'] ?? null,
                'organization' => $attributes['organization'] ?? null,
                'notes' => $attributes['notes'] ?? null,
            ]);

            return ServiceResult::success($contact, 'Contact added.');
        } catch (Throwable $e) {
            report($e);

            return ServiceResult::fromException($e);
        }
    }
}
