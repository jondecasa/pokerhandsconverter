<?php

namespace App\Models;

use Database\Factories\RangeStudyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class RangeStudy extends Model
{
    /** @use HasFactory<RangeStudyFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'sort_order'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<RangeScenario, $this>
     */
    public function scenarios(): HasMany
    {
        return $this->hasMany(RangeScenario::class)
            ->orderBy('group_order')->orderBy('row_order')->orderBy('button_order');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scenarios nested as group_label -> row_label -> [scenarios], preserving
     * their stored order — the shape the button navigation renders from.
     *
     * @return Collection<string, Collection<string, Collection<int, RangeScenario>>>
     */
    public function navigation(): Collection
    {
        return $this->scenarios
            ->groupBy('group_label')
            ->map(fn ($groupScenarios) => $groupScenarios->groupBy('row_label'));
    }

    public function defaultScenario(): ?RangeScenario
    {
        return $this->scenarios->firstWhere('is_default', true) ?? $this->scenarios->first();
    }
}
