<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'coinpoker_id',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /** Store an empty CoinPoker ID as null. */
    protected function coinpokerId(): Attribute
    {
        return Attribute::set(fn (?string $value) => trim((string) $value) ?: null);
    }

    /** Screen name to substitute for "Hero" in converted files. */
    public function heroName(): string
    {
        return $this->coinpoker_id ?: 'Hero';
    }

    /** The package behind the user's active subscription, if it maps to one. */
    public function currentPlan(): ?Plan
    {
        $price = $this->subscription(config('pokercoinverter.subscription_name', 'default'))?->stripe_price;

        if (blank($price)) {
            return null;
        }

        return str_starts_with($price, 'free:')
            ? Plan::where('slug', substr($price, 5))->first()
            : Plan::where('stripe_price_id', $price)->first();
    }

    /**
     * @return HasMany<Conversion, $this>
     */
    public function conversions(): HasMany
    {
        return $this->hasMany(Conversion::class);
    }
}
