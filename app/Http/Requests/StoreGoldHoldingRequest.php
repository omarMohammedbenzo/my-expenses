<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreGoldHoldingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'grams' => 'required|integer|min:1',
            'karat' => 'required|integer|in:9,14,18,21,22,24',
            'manufacturing_cost_per_gram' => 'required|numeric|min:0|max:99999999.99',
            'purchase_price' => 'nullable|numeric|min:0|max:9999999999.99',
            'note' => 'nullable|string|max:255',
        ];
    }
}
