<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoldHolding extends Model
{
    protected $fillable = ['user_id', 'grams', 'karat', 'manufacturing_cost_per_gram', 'purchase_price', 'note'];

    protected function casts(): array
    {
        return [
            'grams' => 'integer',
            'karat' => 'integer',
            'manufacturing_cost_per_gram' => 'decimal:2',
            'purchase_price' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
