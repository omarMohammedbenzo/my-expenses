<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomeSourceRequest;
use App\Http\Requests\UpdateIncomeSourceRequest;
use App\Models\IncomeSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IncomeSourceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $sources = $user->incomeSources()->with('account.institution')->orderBy('name')->get();

        $pending = $user->incomeTransactions()
            ->with('incomeSource')
            ->where('status', 'pending')
            ->orderBy('expected_date')
            ->get();

        $confirmed = $user->incomeTransactions()
            ->with(['incomeSource', 'account.institution'])
            ->where('status', 'confirmed')
            ->orderByDesc('confirmed_date')
            ->limit(20)
            ->get();

        return Inertia::render('Income/Index', [
            'sources' => $sources,
            'pending' => $pending,
            'confirmed' => $confirmed,
            'accounts' => $user->accounts()->with('institution')->orderBy('currency')->get(),
            'enabledCurrencies' => $user->enabledCurrencies(),
        ]);
    }

    public function store(StoreIncomeSourceRequest $request): RedirectResponse
    {
        $request->user()->incomeSources()->create($request->validated());

        return back();
    }

    public function update(UpdateIncomeSourceRequest $request, IncomeSource $incomeSource): RedirectResponse
    {
        $this->authorize('update', $incomeSource);

        $incomeSource->update($request->validated());

        return back();
    }

    public function destroy(IncomeSource $incomeSource): RedirectResponse
    {
        $this->authorize('delete', $incomeSource);

        $incomeSource->delete();

        return back();
    }
}
