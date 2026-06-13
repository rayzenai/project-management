<?php

namespace RayzenAI\ProjectManagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
            'archived_at' => 'datetime',
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
     * @return BelongsToMany<Team, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withTimestamps();
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
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function isArchived(): Attribute
    {
        return Attribute::get(fn (): bool => $this->archived_at !== null);
    }

    public function archive(): void
    {
        if ($this->archived_at === null) {
            $this->forceFill(['archived_at' => now()])->save();
        }
    }

    public function restore(): void
    {
        if ($this->archived_at !== null) {
            $this->forceFill(['archived_at' => null])->save();
        }
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
