<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGoldHoldingRequest extends FormRequest
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
            'note' => 'nullable|string|max:255',
        ];
    }
}
