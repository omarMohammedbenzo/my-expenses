<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncomeSource extends Model
{
    protected $fillable = ['user_id', 'account_id', 'name', 'amount', 'currency', 'frequency', 'expected_day', 'is_active'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(IncomeTransaction::class);
    }

    /**
     * The expected date for the current pay period (monthly day-of-month
     * clamped to the end of the month, or the current week's weekday).
     */
    public function currentExpectedDate(?Carbon $today = null): ?Carbon
    {
        if ($this->expected_day === null) {
            return null;
        }

        $today = $today ?? Carbon::today();

        if ($this->frequency === 'monthly') {
            $day = min($this->expected_day, $today->daysInMonth);

            return $today->copy()->day($day);
        }

        // Weekly: expected_day is 0 (Sunday) through 6 (Saturday).
        return $today->copy()->startOfWeek(Carbon::SUNDAY)->addDays($this->expected_day);
    }
}
