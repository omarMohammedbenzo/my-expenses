<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseIndexRequest;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Account;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(ExpenseIndexRequest $request): Response
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $month = $request->input('month');

        if (! $from && ! $to) {
            $month = $month ?: now()->format('Y-m');
        }

        $enabledCurrencies = $request->user()->enabledCurrencies();
        $currency = $request->input('currency');

        // An unknown (or missing) currency means "all currencies".
        if (! in_array($currency, $enabledCurrencies, true)) {
            $currency = null;
        }

        $applyFilters = function ($query) use ($from, $to, $month, $currency) {
            if ($from || $to) {
                if ($from) {
                    $query->whereDate('date', '>=', $from);
                }
                if ($to) {
                    $query->whereDate('date', '<=', $to);
                }
            } else {
                $query->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$month]);
            }

            if ($currency) {
                $query->whereHas('account', fn ($q) => $q->where('currency', $currency));
            }

            return $query;
        };

        $expenses = $applyFilters($request->user()->expenses()->with(['category', 'account.institution']))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $totals = $applyFilters(
            $request->user()->expenses()->join('accounts', 'accounts.id', '=', 'expenses.account_id')
        )
            ->selectRaw('accounts.currency as currency, SUM(expenses.amount) as total')
            ->groupBy('accounts.currency')
            ->orderBy('accounts.currency')
            ->get();

        $categories = ExpenseCategory::visibleTo($request->user()->id)
            ->orderBy('name')
            ->get();

        $accounts = $request->user()->accounts()
            ->with('institution')
            ->orderBy('currency')
            ->get();

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'categories' => $categories,
            'accounts' => $accounts,
            'totals' => $totals,
            'currency' => $currency,
            'enabledCurrencies' => $enabledCurrencies,
            'month' => $month,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $category = ExpenseCategory::findOrFail($data['category_id']);
        $this->authorize('view', $category);

        $data['affects_balance'] = $data['affects_balance'] ?? true;

        DB::transaction(function () use ($user, $data) {
            $user->expenses()->create($data);

            if ($data['affects_balance']) {
                Account::where('id', $data['account_id'])->decrement('balance', $data['amount']);
            }
        });

        return back();
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->authorize('update', $expense);

        $data = $request->validated();

        $category = ExpenseCategory::findOrFail($data['category_id']);
        $this->authorize('view', $category);

        $data['affects_balance'] = $data['affects_balance'] ?? true;

        DB::transaction(function () use ($expense, $data) {
            // Revert the old amount from the old account, then apply the new amount to the new account.
            // Reference-only expenses were never deducted, so they are skipped on either side.
            if ($expense->affects_balance) {
                Account::where('id', $expense->account_id)->increment('balance', $expense->amount);
            }

            if ($data['affects_balance']) {
                Account::where('id', $data['account_id'])->decrement('balance', $data['amount']);
            }

            $expense->update($data);
        });

        return back();
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);

        DB::transaction(function () use ($expense) {
            if ($expense->affects_balance) {
                Account::where('id', $expense->account_id)->increment('balance', $expense->amount);
            }

            $expense->delete();
        });

        return back();
    }
}
