<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Currency;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'password', 'default_currency', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'default_currency' => 'USD',
    ];

    protected $appends = ['avatar_url'];

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
        ];
    }

    public function currencies(): HasMany
    {
        return $this->hasMany(UserCurrency::class);
    }

    public function institutions(): HasMany
    {
        return $this->hasMany(Institution::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function expenseCategories(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function incomeSources(): HasMany
    {
        return $this->hasMany(IncomeSource::class);
    }

    public function incomeTransactions(): HasMany
    {
        return $this->hasMany(IncomeTransaction::class);
    }

    public function goldHoldings(): HasMany
    {
        return $this->hasMany(GoldHolding::class);
    }

    public function gifts(): HasMany
    {
        return $this->hasMany(Gift::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(Transfer::class);
    }

    public function enabledCurrencies(): array
    {
        return $this->currencies()->where('is_enabled', true)->pluck('currency')->all();
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->avatar ? Storage::disk('public')->url($this->avatar) : null,
        );
    }

    protected static function booted(): void
    {
        static::created(function (User $user) {
            foreach (Currency::values() as $currency) {
                $user->currencies()->create([
                    'currency' => $currency,
                    'is_enabled' => $currency === $user->default_currency,
                ]);
            }
        });
    }
}
