<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmIncomeSourceRequest;
use App\Http\Requests\ConfirmIncomeTransactionRequest;
use App\Models\Account;
use App\Models\IncomeSource;
use App\Models\IncomeTransaction;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IncomeTransactionController extends Controller
{
    public function confirm(ConfirmIncomeTransactionRequest $request, IncomeTransaction $incomeTransaction): RedirectResponse
    {
        $this->authorize('update', $incomeTransaction);

        if ($incomeTransaction->status === 'confirmed') {
            throw ValidationException::withMessages([
                'confirmed_date' => 'income.alreadyConfirmed',
            ]);
        }

        $data = $request->validated();
        $accountId = $data['account_id'] ?? $incomeTransaction->account_id;

        DB::transaction(function () use ($incomeTransaction, $data, $accountId) {
            $incomeTransaction->update([
                'status' => 'confirmed',
                'confirmed_date' => $data['confirmed_date'],
                'account_id' => $accountId,
            ]);

            Account::where('id', $accountId)->increment('balance', $incomeTransaction->amount);
        });

        return back();
    }

    /**
     * Revert a confirmed income transaction back to pending, reversing the
     * balance credit that was applied when it was confirmed.
     */
    public function unconfirm(IncomeTransaction $incomeTransaction): RedirectResponse
    {
        $this->authorize('update', $incomeTransaction);

        if ($incomeTransaction->status !== 'confirmed') {
            return back();
        }

        DB::transaction(function () use ($incomeTransaction) {
            Account::where('id', $incomeTransaction->account_id)->decrement('balance', $incomeTransaction->amount);

            $incomeTransaction->update([
                'status' => 'pending',
                'confirmed_date' => null,
            ]);
        });

        return back();
    }

    /**
     * Manually confirm the current period's income for a source, without
     * depending on the scheduled command having generated a pending entry.
     */
    public function confirmForSource(ConfirmIncomeSourceRequest $request, IncomeSource $incomeSource): RedirectResponse
    {
        $this->authorize('update', $incomeSource);

        $user = $request->user();
        $data = $request->validated();

        $accountId = $data['account_id'] ?? $incomeSource->account_id;
        $expectedDate = $incomeSource->currentExpectedDate(Carbon::parse($data['confirmed_date']));

        $existing = $incomeSource->transactions()
            ->where('expected_date', $expectedDate->toDateString())
            ->first();

        if ($existing && $existing->status === 'confirmed') {
            throw ValidationException::withMessages([
                'confirmed_date' => 'income.alreadyConfirmed',
            ]);
        }

        DB::transaction(function () use ($incomeSource, $existing, $data, $accountId, $expectedDate, $user) {
            $transaction = $existing ?? $incomeSource->transactions()->make([
                'user_id' => $user->id,
                'expected_date' => $expectedDate->toDateString(),
                'amount' => $incomeSource->amount,
                'currency' => $incomeSource->currency,
            ]);

            $transaction->fill([
                'status' => 'confirmed',
                'confirmed_date' => $data['confirmed_date'],
                'account_id' => $accountId,
            ])->save();

            Account::where('id', $accountId)->increment('balance', $incomeSource->amount);
        });

        return back();
    }
}
