<?php

namespace RayzenAI\ProjectManagement\Services\Workspace;

use Illuminate\Support\Facades\DB;
use RayzenAI\ProjectManagement\Models\WorkspaceNote;
use RayzenAI\ProjectManagement\Support\ServiceResult;
use Throwable;

class UpdateWorkspaceNoteService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(WorkspaceNote $note, array $attributes): ServiceResult
    {
        $body = trim((string) ($attributes['body'] ?? ''));
        if ($body === '') {
            return ServiceResult::failure('Note body is required.', 422);
        }

        try {
            DB::transaction(function () use ($note, $attributes, $body): void {
                $note->update([
                    'title' => $this->normalizeTitle($attributes['title'] ?? null),
                    'body' => $body,
                ]);
            });

            return ServiceResult::success($note->fresh(), 'Note updated.');
        } catch (Throwable $e) {
            report($e);

            return ServiceResult::fromException($e);
        }
    }

    private function normalizeTitle(mixed $title): ?string
    {
        $title = trim((string) ($title ?? ''));

        return $title === '' ? null : $title;
    }
}
