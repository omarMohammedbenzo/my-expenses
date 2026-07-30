<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInstitutionRequest;
use App\Http\Requests\UpdateInstitutionRequest;
use App\Models\Institution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionController extends Controller
{
    public function index(Request $request): Response
    {
        $institutions = $request->user()->institutions()
            ->with('accounts')
            ->orderBy('name')
            ->get();

        return Inertia::render('Accounts/Index', [
            'institutions' => $institutions,
            'enabledCurrencies' => $request->user()->enabledCurrencies(),
        ]);
    }

    public function store(StoreInstitutionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('institution-logos', 'public');
        }

        $request->user()->institutions()->create($data);

        return back();
    }

    public function update(UpdateInstitutionRequest $request, Institution $institution): RedirectResponse
    {
        $this->authorize('update', $institution);

        $data = $request->validated();
        unset($data['remove_logo']);

        if ($request->hasFile('logo')) {
            if ($institution->logo) {
                Storage::disk('public')->delete($institution->logo);
            }

            $data['logo'] = $request->file('logo')->store('institution-logos', 'public');
        } elseif ($request->boolean('remove_logo') && $institution->logo) {
            Storage::disk('public')->delete($institution->logo);
            $data['logo'] = null;
        }

        $institution->update($data);

        return back();
    }

    public function destroy(Institution $institution): RedirectResponse
    {
        $this->authorize('delete', $institution);

        if ($institution->logo) {
            Storage::disk('public')->delete($institution->logo);
        }

        $institution->delete();

        return back();
    }
}
