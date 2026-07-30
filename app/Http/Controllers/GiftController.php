<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGiftRequest;
use App\Http\Requests\UpdateGiftRequest;
use App\Models\Account;
use App\Models\Gift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GiftController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Gifts/Index', [
            'gifts' => $user->gifts()->with('account.institution')->orderByDesc('date')->get(),
            'accounts' => $user->accounts()->with('institution')->orderBy('currency')->get(),
        ]);
    }

    public function store(StoreGiftRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        DB::transaction(function () use ($user, $data) {
            $user->gifts()->create($data);

            $this->applyToBalance($data['account_id'], $data['type'], $data['amount']);
        });

        return back();
    }

    public function update(UpdateGiftRequest $request, Gift $gift): RedirectResponse
    {
        $this->authorize('update', $gift);

        $data = $request->validated();

        DB::transaction(function () use ($gift, $data) {
            // Revert the old effect from the old account, then apply the new effect to the new account.
            $this->applyToBalance($gift->account_id, $gift->type, $gift->amount, revert: true);
            $this->applyToBalance($data['account_id'], $data['type'], $data['amount']);

            $gift->update($data);
        });

        return back();
    }

    public function destroy(Gift $gift): RedirectResponse
    {
        $this->authorize('delete', $gift);

        DB::transaction(function () use ($gift) {
            $this->applyToBalance($gift->account_id, $gift->type, $gift->amount, revert: true);

            $gift->delete();
        });

        return back();
    }

    private function applyToBalance(int $accountId, string $type, float $amount, bool $revert = false): void
    {
        $increments = $type === 'received' ? ! $revert : $revert;

        if ($increments) {
            Account::where('id', $accountId)->increment('balance', $amount);
        } else {
            Account::where('id', $accountId)->decrement('balance', $amount);
        }
    }
}
