<?php

namespace App\Http\Controllers;

use App\Enums\Currency;
use App\Http\Requests\UpdateCurrencySettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CurrencySettingController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Settings/Currencies', [
            'currencies' => $request->user()->currencies()->orderBy('currency')->get(),
            'defaultCurrency' => $request->user()->default_currency,
        ]);
    }

    public function update(UpdateCurrencySettingsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        foreach (Currency::values() as $currency) {
            $user->currencies()->updateOrCreate(
                ['currency' => $currency],
                ['is_enabled' => in_array($currency, $data['enabled'], true)]
            );
        }

        $user->update(['default_currency' => $data['default_currency']]);

        return back();
    }
}
