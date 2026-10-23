<?php

use App\Services\GoldPriceService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// The service only needs the framework's cache and HTTP client, so this boots
// the app without the database refresh the Feature suite applies.
uses(TestCase::class);

function isaghaPage(): string
{
    return file_get_contents(base_path('tests/Fixtures/isagha-prices.html'));
}

it('reads both sides of the counter for every karat iSagha lists', function () {
    Http::fake(['market.isagha.com/*' => Http::response(isaghaPage())]);

    $market = app(GoldPriceService::class)->getMarket();

    expect($market['available'])->toBeTrue()
        ->and($market['is_stale'])->toBeFalse()
        ->and($market['updated_at'])->toBe('2026-10-23T02:36:26+03:00');

    expect($market['karats'][21])->toMatchArray([
        'sell' => 6310.0,
        'buy' => 6260.0,
        'spread' => 50.0,
        'change' => -10.0,
        'change_percent' => -0.16,
        'direction' => 'down',
        'derived' => false,
    ]);

    // The local market sits above the global price, which iSagha tags "positive".
    expect($market['karats'][21]['sell_gap'])->toMatchArray(['amount' => 42.25, 'above_global' => true]);
});

it('derives the karats iSagha does not list from its 24K row', function () {
    Http::fake(['market.isagha.com/*' => Http::response(isaghaPage())]);

    $nineKarat = app(GoldPriceService::class)->getMarket()['karats'][9];

    expect($nineKarat['derived'])->toBeTrue()
        ->and($nineKarat['sell'])->toBe(round(7211.5 * 9 / 24, 2));
});

it('keeps the gold pound in EGP and the ounce in USD', function () {
    Http::fake(['market.isagha.com/*' => Http::response(isaghaPage())]);

    $market = app(GoldPriceService::class)->getMarket();

    expect($market['pound']['sell'])->toBe(50480.0)
        ->and($market['ounce']['sell'])->toBe(4025.26)
        // An ounce rising while the local market falls is normal; the sign comes
        // from the arrow, not from the figure.
        ->and($market['ounce']['change'])->toBe(0.9)
        ->and($market['usd_per_gram_24k'])->toBe(round(4025.26 / 31.1034768, 4));
});

it('collects the long-range charts up to two years, per karat', function () {
    Http::fake(['market.isagha.com/*' => Http::response(isaghaPage())]);

    $charts = app(GoldPriceService::class)->getMarket()['charts'];

    // Five-year charts are published alongside these and deliberately dropped.
    expect($charts)->toHaveCount(6)
        ->and(collect($charts)->pluck('range')->unique()->sort()->values()->all())
        ->toBe(['one-year', 'two-year'])
        ->and(collect($charts)->pluck('karat')->unique()->sort()->values()->all())
        ->toBe([18, 21, 24]);

    $twoYear21 = collect($charts)->firstWhere(fn ($chart) => $chart['range'] === 'two-year' && $chart['karat'] === 21);

    expect($twoYear21['growth'])->toBe(69.0)
        ->and($twoYear21['url'])->toContain('two-year-gold-21-ar.png')
        ->and($twoYear21['url_en'])->toContain('two-year-gold-21-en.png');
});

it('serves the last good snapshot when the source goes down', function () {
    Http::fake([
        'market.isagha.com/*' => Http::sequence()
            ->push(isaghaPage())
            ->push('', 503),
    ]);

    $service = app(GoldPriceService::class);
    $service->getMarket();

    // Expire the fresh window the way the cache would, leaving only the backup.
    cache()->forget('isagha_gold_market');

    $market = $service->getMarket();

    expect($market['is_stale'])->toBeTrue()
        ->and($market['karats'][21]['sell'])->toBe(6310.0);
});

it('reports prices as unavailable rather than guessing when there is nothing cached', function () {
    Http::fake(['market.isagha.com/*' => Http::response('<html><body>maintenance</body></html>')]);

    $market = app(GoldPriceService::class)->getMarket();

    expect($market['available'])->toBeFalse()
        ->and($market['karats'])->toBe([]);
});

it('stops hammering a source that just failed', function () {
    Http::fake(['market.isagha.com/*' => Http::response('', 500)]);

    $service = app(GoldPriceService::class);
    $service->getMarket();
    $service->getMarket();

    Http::assertSentCount(1);
});
