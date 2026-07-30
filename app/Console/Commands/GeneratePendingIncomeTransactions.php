<?php

namespace App\Console\Commands;

use App\Models\IncomeSource;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('income:generate-pending')]
#[Description('Generate pending income transactions for sources whose expected date is approaching or has arrived')]
class GeneratePendingIncomeTransactions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $today = Carbon::today();
        $lookahead = $today->copy()->addDays(3);

        $count = 0;

        IncomeSource::query()->where('is_active', true)->each(function (IncomeSource $source) use ($today, $lookahead, &$count) {
            $expectedDate = $source->currentExpectedDate($today);

            if ($expectedDate === null || $expectedDate->gt($lookahead)) {
                return;
            }

            $transaction = $source->transactions()->firstOrCreate(
                ['expected_date' => $expectedDate->toDateString()],
                [
                    'user_id' => $source->user_id,
                    'account_id' => $source->account_id,
                    'amount' => $source->amount,
                    'currency' => $source->currency,
                    'status' => 'pending',
                ]
            );

            if ($transaction->wasRecentlyCreated) {
                $count++;
            }
        });

        $this->info("Generated {$count} pending income transaction(s).");
    }
}
