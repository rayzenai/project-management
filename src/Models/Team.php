<?php

namespace RayzenAI\ProjectManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use RayzenAI\ProjectManagement\Database\Factories\TeamFactory;

class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    protected $table = 'teams';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
    ];

    protected static function booted(): void
    {
        static::saving(function (Team $team) {
            if (empty($team->slug)) {
                $base = Str::slug($team->name) ?: 'team';
                $slug = $base;
                $i = 2;
                while (self::query()->where('slug', $slug)->whereKeyNot($team->getKey())->exists()) {
                    $slug = $base.'-'.$i;
                    $i++;
                }
                $team->slug = $slug;
            }
        });
    }

    /**
     * @return BelongsToMany<Member, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class)->withTimestamps();
    }

    /**
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withTimestamps();
    }

    protected static function newFactory(): TeamFactory
    {
        return TeamFactory::new();
    }
}
