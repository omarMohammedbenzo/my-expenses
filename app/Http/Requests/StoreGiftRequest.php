<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGiftRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'required|in:received,given',
            'amount' => 'required|numeric|min:0.01',
            'currency' => ['required', 'string', Rule::in($this->user()->enabledCurrencies())],
            'source_name' => 'required|string|max:255',
            'date' => 'required|date',
            'note' => 'nullable|string|max:255',
        ];
    }
}
