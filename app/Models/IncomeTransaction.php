<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomeTransaction extends Model
{
    protected $fillable = [
        'user_id', 'income_source_id', 'account_id', 'expected_date',
        'confirmed_date', 'amount', 'currency', 'status',
    ];

    protected function casts(): array
    {
        return [
            'expected_date' => 'date:Y-m-d',
            'confirmed_date' => 'date:Y-m-d',
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function incomeSource(): BelongsTo
    {
        return $this->belongsTo(IncomeSource::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
