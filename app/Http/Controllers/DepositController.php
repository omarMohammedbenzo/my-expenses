<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepositRequest;
use App\Http\Requests\UpdateDepositRequest;
use App\Models\Account;
use App\Models\Deposit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class DepositController extends Controller
{
    public function store(StoreDepositRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        DB::transaction(function () use ($user, $data) {
            $user->deposits()->create($data);

            Account::where('id', $data['account_id'])->increment('balance', $data['amount']);
        });

        return back();
    }

    public function update(UpdateDepositRequest $request, Deposit $deposit): RedirectResponse
    {
        $this->authorize('update', $deposit);

        $data = $request->validated();

        DB::transaction(function () use ($deposit, $data) {
            // Revert the old amount from the old account, then apply the new amount to the new account.
            Account::where('id', $deposit->account_id)->decrement('balance', $deposit->amount);
            Account::where('id', $data['account_id'])->increment('balance', $data['amount']);

            $deposit->update($data);
        });

        return back();
    }

    public function destroy(Deposit $deposit): RedirectResponse
    {
        $this->authorize('delete', $deposit);

        DB::transaction(function () use ($deposit) {
            Account::where('id', $deposit->account_id)->decrement('balance', $deposit->amount);

            $deposit->delete();
        });

        return back();
    }
}
