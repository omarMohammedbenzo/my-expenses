<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'institution_id' => [
                'required',
                Rule::exists('institutions', 'id')->where('user_id', $this->user()->id),
            ],
            'currency' => ['required', 'string', Rule::in($this->user()->enabledCurrencies())],
            'nickname' => 'nullable|string|max:255',
            'balance' => 'nullable|numeric|min:0',
        ];
    }
}
