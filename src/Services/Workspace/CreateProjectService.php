<?php

namespace RayzenAI\ProjectManagement\Services\Workspace;

use Illuminate\Support\Str;
use RayzenAI\ProjectManagement\Models\Project;
use RayzenAI\ProjectManagement\Support\ServiceResult;
use Throwable;

class CreateProjectService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): ServiceResult
    {
        $title = trim((string) ($attributes['title'] ?? ''));
        if ($title === '') {
            return ServiceResult::failure('Title is required.', 422);
        }

        try {
            $project = Project::create([
                'title' => $title,
                'slug' => $this->uniqueSlug($title),
                'title_np' => $attributes['title_np'] ?? null,
                'description' => $attributes['description'] ?? null,
                'description_np' => $attributes['description_np'] ?? null,
                'is_public' => (bool) ($attributes['is_public'] ?? false),
            ]);

            return ServiceResult::success($project, 'Project created.');
        } catch (Throwable $e) {
            report($e);

            return ServiceResult::fromException($e);
        }
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'project';
        }
        $slug = $base;
        $i = 2;
        while (Project::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
