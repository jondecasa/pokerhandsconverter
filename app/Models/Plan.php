<?php

namespace App\Models;

use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description',
        'price', 'currency', 'interval', 'stripe_price_id',
        'stakes_cap', 'stakes_label', 'features', 'trial_days',
        'is_visible', 'is_active', 'is_highlighted', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'features' => 'array',
            'trial_days' => 'integer',
            'is_visible' => 'boolean',
            'is_active' => 'boolean',
            'is_highlighted' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Plans a customer may subscribe to (active), listed for the public. */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true)->where('is_active', true);
    }

    /** Any plan that can still be subscribed to (visible or unlisted). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    public function effectiveTrialDays(): int
    {
        return $this->trial_days ?? (int) config('pokercoinverter.trial_days', 0);
    }

    public function priceLabel(): string
    {
        $symbol = $this->currency === 'USD' ? '$' : '';
        $amount = rtrim(rtrim(number_format((float) $this->price, 2), '0'), '.');

        return $symbol.$amount.($symbol === '' ? ' '.$this->currency : '');
    }

    public function stakesText(): ?string
    {
        if (filled($this->stakes_label)) {
            return $this->stakes_label;
        }

        return filled($this->stakes_cap) ? 'Covers up to '.$this->stakes_cap : null;
    }

    /** @return array<int, string> */
    public function featureList(): array
    {
        return array_values(array_filter(array_map('trim', $this->features ?? [])));
    }
}
