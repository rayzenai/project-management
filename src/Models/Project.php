<?php

namespace RayzenAI\ProjectManagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RayzenAI\ProjectManagement\Database\Factories\ProjectFactory;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected $table = 'projects';

    protected $fillable = [
        'slug',
        'title',
        'title_np',
        'description',
        'description_np',
        'is_public',
        'starts_at',
        'ends_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('sort_order')->orderByRaw("CAST(metadata->>'item_number' AS INTEGER) ASC NULLS LAST");
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Short, compact label suitable for cards and chips.
     * Collapses any title containing "100-Day Plan" down to just "100-Day".
     *
     * @return Attribute<string, never>
     */
    protected function shortLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $title = (string) ($this->title ?? '');

            if (str_contains($title, '100-Day Plan')) {
                return '100-Day';
            }

            return $title;
        });
    }

    protected static function newFactory(): ProjectFactory
    {
        return ProjectFactory::new();
    }
}
