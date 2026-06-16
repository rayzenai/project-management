<?php

namespace RayzenAI\ProjectManagement\Services\Workspace;

use Illuminate\Database\Eloquent\Model;
use RayzenAI\ProjectManagement\Support\ServiceResult;
use Throwable;

class RestoreWorkspaceModel
{
    public function execute(Model $model): ServiceResult
    {
        try {
            if (method_exists($model, 'trashed') && ! $model->trashed()) {
                return ServiceResult::success($model, 'Already restored.');
            }

            $this->ensureUniqueSlug($model);
            $model->restore();

            return ServiceResult::success($model->fresh(), 'Restored.');
        } catch (Throwable $e) {
            report($e);

            return ServiceResult::fromException($e);
        }
    }

    private function ensureUniqueSlug(Model $model): void
    {
        if (empty($model->slug)) {
            return;
        }

        $base = $model->slug;
        $i = 1;
        while ($model->newQuery()
            ->where('slug', $model->slug)
            ->whereKeyNot($model->getKey())
            ->exists()
        ) {
            $model->slug = $base.'-'.(++$i);
        }
    }
}
