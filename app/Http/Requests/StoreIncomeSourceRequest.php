<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncomeSourceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'currency' => ['required', 'string', Rule::in($this->user()->enabledCurrencies())],
            'frequency' => 'required|in:monthly,weekly',
            'expected_day' => 'required|integer|min:0|max:31',
            'account_id' => [
                'nullable',
                Rule::exists('accounts', 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }
}
