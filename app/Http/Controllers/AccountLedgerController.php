<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\IncomeTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountLedgerController extends Controller
{
    public function show(Request $request, Account $account): Response
    {
        $this->authorize('view', $account);

        $account->load('institution');

        $month = $request->input('month');

        $deposits = $account->deposits()->get()->map(fn ($deposit) => [
            'id' => 'deposit-'.$deposit->id,
            'raw_id' => $deposit->id,
            'type' => 'deposit',
            'direction' => 'in',
            'date' => $deposit->date->format('Y-m-d'),
            'amount' => $deposit->amount,
            'label' => $deposit->source,
            'note' => $deposit->note,
            'created_at' => $deposit->created_at,
        ]);

        $expenses = $account->expenses()->with('category')->get()->map(fn ($expense) => [
            'id' => 'expense-'.$expense->id,
            'type' => 'expense',
            'direction' => 'out',
            'date' => $expense->date->format('Y-m-d'),
            'amount' => $expense->amount,
            'label' => $expense->category?->name,
            'note' => $expense->note,
            'created_at' => $expense->created_at,
        ]);

        $gifts = $account->gifts()->get()->map(fn ($gift) => [
            'id' => 'gift-'.$gift->id,
            'type' => 'gift',
            'direction' => $gift->type === 'received' ? 'in' : 'out',
            'date' => $gift->date->format('Y-m-d'),
            'amount' => $gift->amount,
            'label' => $gift->source_name,
            'note' => $gift->note,
            'created_at' => $gift->created_at,
        ]);

        $confirmedIncome = IncomeTransaction::query()
            ->where('account_id', $account->id)
            ->where('status', 'confirmed')
            ->with('incomeSource')
            ->get()
            ->map(fn ($transaction) => [
                'id' => 'income-'.$transaction->id,
                'type' => 'income',
                'direction' => 'in',
                'date' => $transaction->confirmed_date->format('Y-m-d'),
                'amount' => $transaction->amount,
                'label' => $transaction->incomeSource?->name,
                'note' => null,
                'created_at' => $transaction->updated_at,
            ]);

        $transactions = $deposits
            ->concat($expenses)
            ->concat($gifts)
            ->concat($confirmedIncome)
            ->when($month, fn ($rows) => $rows->filter(fn ($row) => str_starts_with($row['date'], $month)))
            ->sortByDesc(fn ($row) => $row['date'].' '.$row['created_at'])
            ->values();

        $total = $transactions->sum(fn ($row) => $row['direction'] === 'in' ? $row['amount'] : -$row['amount']);

        return Inertia::render('Accounts/Ledger', [
            'account' => $account,
            'transactions' => $transactions,
            'month' => $month,
            'total' => $total,
        ]);
    }
}
