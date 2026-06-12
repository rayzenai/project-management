<?php

namespace RayzenAI\ProjectManagement\Http\Requests\Concerns;

use Illuminate\Support\Facades\Gate;

/**
 * Member management mutates host logins (creates users, resets passwords,
 * deletes accounts), so it must be gateable. When the host app defines a
 * `manage-workspace-members` Gate ability, it decides who may do this; when
 * it doesn't, any authenticated workspace user may (the package's default
 * single-team trust model).
 */
trait AuthorizesMemberManagement
{
    public function authorize(): bool
    {
        if (! $this->user()) {
            return false;
        }

        return Gate::has('manage-workspace-members')
            ? $this->user()->can('manage-workspace-members')
            : true;
    }
}
