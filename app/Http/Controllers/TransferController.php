<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransferRequest;
use App\Http\Requests\UpdateTransferRequest;
use App\Models\Account;
use App\Models\Transfer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TransferController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Transfers/Index', [
            'transfers' => $user->transfers()->with(['fromAccount.institution', 'toAccount.institution'])->orderByDesc('date')->orderByDesc('id')->get(),
            'accounts' => $user->accounts()->with('institution')->orderBy('currency')->get(),
        ]);
    }

    public function store(StoreTransferRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        DB::transaction(function () use ($user, $data) {
            $user->transfers()->create($data);

            $this->applyToBalances($data['from_account_id'], $data['to_account_id'], $data['sent_amount'], $data['received_amount']);
        });

        return back();
    }

    public function update(UpdateTransferRequest $request, Transfer $transfer): RedirectResponse
    {
        $this->authorize('update', $transfer);

        $data = $request->validated();

        DB::transaction(function () use ($transfer, $data) {
            // Revert the old effect, then apply the new one.
            $this->applyToBalances($transfer->from_account_id, $transfer->to_account_id, $transfer->sent_amount, $transfer->received_amount, revert: true);
            $this->applyToBalances($data['from_account_id'], $data['to_account_id'], $data['sent_amount'], $data['received_amount']);

            $transfer->update($data);
        });

        return back();
    }

    public function destroy(Transfer $transfer): RedirectResponse
    {
        $this->authorize('delete', $transfer);

        DB::transaction(function () use ($transfer) {
            $this->applyToBalances($transfer->from_account_id, $transfer->to_account_id, $transfer->sent_amount, $transfer->received_amount, revert: true);

            $transfer->delete();
        });

        return back();
    }

    private function applyToBalances(int $fromAccountId, int $toAccountId, float $sentAmount, float $receivedAmount, bool $revert = false): void
    {
        if ($revert) {
            Account::where('id', $fromAccountId)->increment('balance', $sentAmount);
            Account::where('id', $toAccountId)->decrement('balance', $receivedAmount);
        } else {
            Account::where('id', $fromAccountId)->decrement('balance', $sentAmount);
            Account::where('id', $toAccountId)->increment('balance', $receivedAmount);
        }
    }
}
