<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $enabledCurrencies = $user->enabledCurrencies();

        $month = $request->input('month') ?: now()->format('Y-m');
        $currency = $request->input('currency', $user->default_currency);

        if (! in_array($currency, $enabledCurrencies, true)) {
            $currency = $enabledCurrencies[0] ?? $user->default_currency;
        }

        $categoryBreakdown = DB::table('expenses')
            ->join('accounts', 'accounts.id', '=', 'expenses.account_id')
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.category_id')
            ->where('expenses.user_id', $user->id)
            ->where('accounts.currency', $currency)
            ->whereRaw("DATE_FORMAT(expenses.date, '%Y-%m') = ?", [$month])
            ->groupBy('expense_categories.id', 'expense_categories.name', 'expense_categories.icon')
            ->orderByDesc(DB::raw('SUM(expenses.amount)'))
            ->select('expense_categories.id', 'expense_categories.name', 'expense_categories.icon', DB::raw('SUM(expenses.amount) as total'))
            ->get();

        $totalSpent = $categoryBreakdown->sum('total');
        $topCategory = $categoryBreakdown->first();

        $accountBalances = $user->accounts()
            ->with('institution')
            ->where('currency', $currency)
            ->orderBy('balance', 'desc')
            ->get()
            ->map(fn ($account) => [
                'id' => $account->id,
                'label' => $account->displayName(),
                'institution' => $account->institution->name,
                'color' => $account->institution->color,
                'balance' => $account->balance,
            ]);

        $totalBalance = $accountBalances->sum('balance');

        $pendingIncome = $user->incomeTransactions()
            ->with('incomeSource')
            ->where('status', 'pending')
            ->orderBy('expected_date')
            ->get();

        return Inertia::render('Dashboard', [
            'month' => $month,
            'currency' => $currency,
            'enabledCurrencies' => $enabledCurrencies,
            'totalSpent' => $totalSpent,
            'topCategory' => $topCategory,
            'categoryBreakdown' => $categoryBreakdown,
            'accountBalances' => $accountBalances,
            'totalBalance' => $totalBalance,
            'pendingIncome' => $pendingIncome,
        ]);
    }
}
