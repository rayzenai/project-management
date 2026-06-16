<?php

namespace RayzenAI\ProjectManagement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public static $wrap = null;

    /** @return array<string,mixed> */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'data' => $this->data,
        ];
    }
}
