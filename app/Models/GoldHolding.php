<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoldHolding extends Model
{
    protected $fillable = ['user_id', 'grams', 'karat', 'note'];

    protected function casts(): array
    {
        return [
            'grams' => 'integer',
            'karat' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
