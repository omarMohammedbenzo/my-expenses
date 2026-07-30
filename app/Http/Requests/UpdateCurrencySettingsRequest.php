<?php

namespace App\Http\Requests;

use App\Enums\Currency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCurrencySettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'enabled' => 'required|array',
            'enabled.*' => ['string', Rule::in(Currency::values())],
            'default_currency' => ['required', 'string', Rule::in(Currency::values())],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $enabled = $this->input('enabled', []);
            $default = $this->input('default_currency');

            if (empty($enabled)) {
                $validator->errors()->add('enabled', 'At least one currency must be enabled.');

                return;
            }

            if (! in_array($default, $enabled, true)) {
                $validator->errors()->add('default_currency', 'The default currency must be one of the enabled currencies.');
            }
        });
    }
}
