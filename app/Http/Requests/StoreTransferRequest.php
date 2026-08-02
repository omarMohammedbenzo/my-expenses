<?php

namespace App\Http\Requests;

use App\Models\Account;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTransferRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from_account_id' => [
                'required',
                'different:to_account_id',
                Rule::exists('accounts', 'id')->where('user_id', $this->user()->id),
            ],
            'to_account_id' => [
                'required',
                Rule::exists('accounts', 'id')->where('user_id', $this->user()->id),
            ],
            'sent_amount' => 'required|numeric|min:0.01',
            'received_amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'note' => 'nullable|string|max:255',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $account = Account::find($this->input('from_account_id'));

            if ($account && $account->balance < (float) $this->input('sent_amount')) {
                $validator->errors()->add('sent_amount', 'insufficient_balance');
            }
        });
    }
}
