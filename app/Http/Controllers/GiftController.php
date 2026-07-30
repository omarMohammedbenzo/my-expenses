<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGiftRequest;
use App\Http\Requests\UpdateGiftRequest;
use App\Models\Gift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GiftController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Gifts/Index', [
            'gifts' => $user->gifts()->orderByDesc('date')->get(),
            'enabledCurrencies' => $user->enabledCurrencies(),
        ]);
    }

    public function store(StoreGiftRequest $request): RedirectResponse
    {
        $request->user()->gifts()->create($request->validated());

        return back();
    }

    public function update(UpdateGiftRequest $request, Gift $gift): RedirectResponse
    {
        $this->authorize('update', $gift);

        $gift->update($request->validated());

        return back();
    }

    public function destroy(Gift $gift): RedirectResponse
    {
        $this->authorize('delete', $gift);

        $gift->delete();

        return back();
    }
}
