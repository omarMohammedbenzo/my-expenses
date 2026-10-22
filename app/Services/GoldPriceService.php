<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reads the live Egyptian gold market off iSagha's public price table.
 *
 * iSagha renders one row per unit (karats, the gold pound, the global ounce)
 * carrying both sides of the counter — what a dealer sells at and what they buy
 * back at — plus the spread against the global price and the move since the last
 * update. We keep all of it: the buy side is what a holding is actually worth if
 * sold today, which is the only honest basis for a profit figure.
 */
class GoldPriceService
{
    private const TROY_OUNCE_IN_GRAMS = 31.1034768;

    private const SOURCE_URL = 'https://market.isagha.com/prices';

    private const CACHE_KEY = 'isagha_gold_market';

    /** Last successful read, kept far longer than the fresh window so an outage degrades instead of breaking. */
    private const LAST_GOOD_CACHE_KEY = 'isagha_gold_market_last_good';

    /** Set after a failed read so a source that is down cannot slow down every page load. */
    private const COOLDOWN_CACHE_KEY = 'isagha_gold_market_cooldown';

    private const FRESH_FOR_MINUTES = 10;

    private const LAST_GOOD_FOR_HOURS = 48;

    private const COOLDOWN_MINUTES = 3;

    /** Karats the app lets you record. iSagha lists all but 9, which we derive from its 24K row. */
    private const SUPPORTED_KARATS = [9, 14, 18, 21, 22, 24];

    /**
     * The whole market snapshot, ready to hand to the page.
     *
     * @return array<string, mixed>
     */
    public function getMarket(): array
    {
        $fresh = Cache::get(self::CACHE_KEY);

        if (is_array($fresh)) {
            return $fresh;
        }

        // A cooldown entry means the last read failed moments ago. Retrying on
        // every request is what made this page crawl, so serve the last good
        // snapshot and let the cooldown expire first.
        if (Cache::get(self::COOLDOWN_CACHE_KEY)) {
            return $this->lastGoodOrEmpty();
        }

        $market = $this->fetchMarket();

        if ($market === null) {
            Cache::put(self::COOLDOWN_CACHE_KEY, true, now()->addMinutes(self::COOLDOWN_MINUTES));

            return $this->lastGoodOrEmpty();
        }

        Cache::put(self::CACHE_KEY, $market, now()->addMinutes(self::FRESH_FOR_MINUTES));
        Cache::put(self::LAST_GOOD_CACHE_KEY, $market, now()->addHours(self::LAST_GOOD_FOR_HOURS));
        Cache::forget(self::COOLDOWN_CACHE_KEY);

        return $market;
    }

    /**
     * Global spot price per gram of pure gold, in USD.
     */
    public function getPricePerGramUsd(): ?float
    {
        return $this->getMarket()['usd_per_gram_24k'];
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::LAST_GOOD_CACHE_KEY);
        Cache::forget(self::COOLDOWN_CACHE_KEY);
    }

    private function fetchMarket(): ?array
    {
        try {
            $response = Http::connectTimeout(3)
                ->timeout(7)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml',
                    'Accept-Language' => 'ar,en;q=0.8',
                ])
                ->get(self::SOURCE_URL);

            if (! $response->successful()) {
                Log::warning('iSagha returned a non-success status', ['status' => $response->status()]);

                return null;
            }

            return $this->parseMarket($response->body());
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch iSagha gold prices', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function parseMarket(string $html): ?array
    {
        $rows = $this->parseRows($html);

        // 24K anchors every derived figure, so without it the snapshot is not
        // worth caching — better to keep serving the previous one.
        if (! isset($rows['24'])) {
            Log::warning('iSagha price table parsed without a 24K row', ['units' => array_keys($rows)]);

            return null;
        }

        $karats = [];

        foreach (self::SUPPORTED_KARATS as $karat) {
            $karats[$karat] = $rows[(string) $karat] ?? $this->deriveKarat($rows['24'], $karat);
        }

        $ounce = $rows['ounce'] ?? null;

        return [
            'available' => true,
            'is_stale' => false,
            'karats' => $karats,
            'pound' => $rows['pound'] ?? null,
            'ounce' => $ounce,
            'usd_per_gram_24k' => $ounce !== null
                ? round($ounce['sell'] / self::TROY_OUNCE_IN_GRAMS, 4)
                : null,
            'updated_at' => $this->parseUpdatedAt($html),
            'fetched_at' => now()->toIso8601String(),
            'source_url' => self::SOURCE_URL,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function parseRows(string $html): array
    {
        $pattern = '/<tr\b[^>]*data-testid="prices-row"[^>]*data-unit="([a-z0-9]+)"[^>]*>(.*?)<\/tr>/su';

        if (! preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $rows = [];

        foreach ($matches as $match) {
            $unit = $match[1];

            // iSagha prints the same table twice (desktop and mobile); the first
            // copy carries the same figures, so later ones are noise.
            if (isset($rows[$unit])) {
                continue;
            }

            $row = $this->rowFromCells($this->parseCells($match[2]));

            if ($row !== null) {
                $rows[$unit] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return array<int, array{0: string, 1: string}> attributes and inner HTML per cell
     */
    private function parseCells(string $rowHtml): array
    {
        preg_match_all('/<td\b([^>]*)>(.*?)<\/td>/su', $rowHtml, $matches, PREG_SET_ORDER);

        return array_map(fn (array $cell) => [$cell[1], $cell[2]], $matches);
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $cells
     */
    private function rowFromCells(array $cells): ?array
    {
        // Seven columns is the full table (both global-spread columns present);
        // five is the same table with those two columns dropped.
        $layout = match (count($cells)) {
            7 => ['sell' => 1, 'sell_gap' => 2, 'buy' => 3, 'buy_gap' => 4, 'change' => 5, 'percent' => 6],
            5 => ['sell' => 1, 'buy' => 2, 'change' => 3, 'percent' => 4],
            default => null,
        };

        if ($layout === null) {
            return null;
        }

        $sell = $this->numberIn($cells[$layout['sell']][1]);
        $buy = $this->numberIn($cells[$layout['buy']][1]);

        if ($sell === null || $buy === null) {
            return null;
        }

        $change = $this->signedNumberIn($cells[$layout['change']][1]);

        return [
            'sell' => $sell,
            'buy' => $buy,
            'spread' => round($sell - $buy, 2),
            'sell_gap' => isset($layout['sell_gap']) ? $this->gapIn($cells[$layout['sell_gap']]) : null,
            'buy_gap' => isset($layout['buy_gap']) ? $this->gapIn($cells[$layout['buy_gap']]) : null,
            'change' => $change,
            'change_percent' => $this->signedNumberIn($cells[$layout['percent']][1]),
            'direction' => $this->directionOf($change),
            'derived' => false,
        ];
    }

    /**
     * Local-versus-global spread for one side of the counter.
     *
     * @param  array{0: string, 1: string}  $cell
     * @return array{amount: float, above_global: bool}|null
     */
    private function gapIn(array $cell): ?array
    {
        $amount = $this->numberIn($cell[1]);

        if ($amount === null) {
            return null;
        }

        return [
            'amount' => abs($amount),
            // iSagha tags the cell "positive" when the local price sits above the
            // global one — a premium paid locally, not a gain.
            'above_global' => str_contains($cell[0], 'data-gap="positive"'),
        ];
    }

    /**
     * 9K, and anything else iSagha stops listing, scaled off its 24K row.
     */
    private function deriveKarat(array $row24, int $karat): array
    {
        $ratio = $karat / 24;

        return [
            'sell' => round($row24['sell'] * $ratio, 2),
            'buy' => round($row24['buy'] * $ratio, 2),
            'spread' => round(($row24['sell'] - $row24['buy']) * $ratio, 2),
            'sell_gap' => null,
            'buy_gap' => null,
            'change' => $row24['change'] !== null ? round($row24['change'] * $ratio, 2) : null,
            'change_percent' => $row24['change_percent'],
            'direction' => $row24['direction'],
            'derived' => true,
        ];
    }

    private function parseUpdatedAt(string $html): ?string
    {
        if (preg_match('/<time\b[^>]*datetime="([^"]+)"[^>]*data-testid="prices-updated"/u', $html, $match)) {
            return $match[1];
        }

        return null;
    }

    /**
     * Plain figure out of a cell, ignoring markup, bidi marks and the currency word.
     */
    private function numberIn(string $cellHtml): ?float
    {
        $text = str_replace(',', '', $this->textOf($cellHtml));

        if (! preg_match('/-?\d+(?:\.\d+)?/', $text, $match)) {
            return null;
        }

        return (float) $match[0];
    }

    /**
     * Change figures carry their sign in an arrow and an aria-label rather than
     * consistently on the number itself, so the label decides the direction and
     * the number only supplies the magnitude.
     */
    private function signedNumberIn(string $cellHtml): ?float
    {
        $value = $this->numberIn($cellHtml);

        if ($value === null) {
            return null;
        }

        if (str_contains($cellHtml, 'انخفاض') || str_contains($cellHtml, '▼')) {
            return -abs($value);
        }

        if (str_contains($cellHtml, 'ارتفاع') || str_contains($cellHtml, '▲')) {
            return abs($value);
        }

        return $value;
    }

    private function directionOf(?float $change): string
    {
        return match (true) {
            $change === null, abs($change) < 0.0001 => 'flat',
            $change > 0 => 'up',
            default => 'down',
        };
    }

    private function textOf(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Bidi marks and no-break spaces survive strip_tags and would otherwise
        // sit between a minus sign and its digits.
        return trim(preg_replace('/[\x{200E}\x{200F}\x{00A0}\x{2066}-\x{2069}]/u', '', $text));
    }

    /**
     * @return array<string, mixed>
     */
    private function lastGoodOrEmpty(): array
    {
        $lastGood = Cache::get(self::LAST_GOOD_CACHE_KEY);

        if (is_array($lastGood)) {
            return ['is_stale' => true] + $lastGood;
        }

        return [
            'available' => false,
            'is_stale' => false,
            'karats' => [],
            'pound' => null,
            'ounce' => null,
            'usd_per_gram_24k' => null,
            'updated_at' => null,
            'fetched_at' => null,
            'source_url' => self::SOURCE_URL,
        ];
    }
}
