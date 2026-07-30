<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoldPriceService
{
    private const TROY_OUNCE_IN_GRAMS = 31.1034768;

    private const PRICE_CACHE_KEY = 'gold_price_per_gram_usd_24k';

    private const EGP_PRICES_CACHE_KEY = 'gold_price_per_gram_egp_by_karat';

    private const SUPPORTED_KARATS = [9, 14, 18, 21, 22, 24];

    public function getPricePerGramUsd(): ?float
    {
        return Cache::remember(self::PRICE_CACHE_KEY, now()->addMinutes(15), function () {
            try {
                $response = Http::timeout(5)->get('https://api.gold-api.com/price/XAU');

                if (! $response->successful()) {
                    return null;
                }

                $pricePerOunce = $response->json('price');

                if (! is_numeric($pricePerOunce)) {
                    return null;
                }

                return round($pricePerOunce / self::TROY_OUNCE_IN_GRAMS, 4);
            } catch (\Throwable $e) {
                Log::warning('Failed to fetch gold price', ['error' => $e->getMessage()]);

                return null;
            }
        });
    }

    public function getPricePerGramForKarat(int $karat): ?float
    {
        $price24k = $this->getPricePerGramUsd();

        if ($price24k === null) {
            return null;
        }

        return round($price24k * ($karat / 24), 4);
    }

    /**
     * Real Egyptian jewelry-market sell price per gram, keyed by karat, scraped from iSagha.
     * Karats iSagha doesn't list directly (9, 14) are derived from its 24K price.
     *
     * @return array<int, float>
     */
    public function getEgpPricesByKarat(): array
    {
        $scraped = Cache::remember(self::EGP_PRICES_CACHE_KEY, now()->addMinutes(15), function () {
            try {
                $response = Http::timeout(8)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; MyExpensesApp/1.0)'])
                    ->get('https://market.isagha.com/prices');

                if (! $response->successful()) {
                    return null;
                }

                preg_match_all(
                    '/عيار\s*(\d+)<\/span>.*?>\s*(\d+(?:\.\d+)?)\s*ج\.م/su',
                    $response->body(),
                    $matches,
                    PREG_SET_ORDER
                );

                $prices = [];

                foreach ($matches as $match) {
                    $karat = (int) $match[1];

                    if (in_array($karat, self::SUPPORTED_KARATS, true)) {
                        $prices[$karat] = (float) $match[2];
                    }
                }

                return $prices !== [] ? $prices : null;
            } catch (\Throwable $e) {
                Log::warning('Failed to fetch iSagha gold prices', ['error' => $e->getMessage()]);

                return null;
            }
        });

        if ($scraped === null) {
            return [];
        }

        $price24k = $scraped[24] ?? null;

        foreach (self::SUPPORTED_KARATS as $karat) {
            if (! isset($scraped[$karat]) && $price24k !== null) {
                $scraped[$karat] = round($price24k * ($karat / 24), 4);
            }
        }

        return $scraped;
    }
}
