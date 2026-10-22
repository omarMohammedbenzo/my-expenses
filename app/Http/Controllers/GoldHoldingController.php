<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGoldHoldingRequest;
use App\Http\Requests\UpdateGoldHoldingRequest;
use App\Models\GoldHolding;
use App\Services\GoldPriceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GoldHoldingController extends Controller
{
    public function __construct(private readonly GoldPriceService $goldPriceService) {}

    public function index(Request $request): Response
    {
        // iSagha only moves a few times an hour, so the snapshot is cached. The
        // refresh flag is the way to insist on a fresh read before it expires.
        if ($request->boolean('refresh')) {
            $this->goldPriceService->forget();
        }

        return Inertia::render('Gold/Index', [
            'holdings' => $request->user()->goldHoldings()->orderByDesc('created_at')->get(),
            'market' => $this->goldPriceService->getMarket(),
        ]);
    }

    public function store(StoreGoldHoldingRequest $request): RedirectResponse
    {
        $request->user()->goldHoldings()->create($request->validated());

        return back();
    }

    public function update(UpdateGoldHoldingRequest $request, GoldHolding $goldHolding): RedirectResponse
    {
        $this->authorize('update', $goldHolding);

        $goldHolding->update($request->validated());

        return back();
    }

    public function destroy(GoldHolding $goldHolding): RedirectResponse
    {
        $this->authorize('delete', $goldHolding);

        $goldHolding->delete();

        return back();
    }
}
