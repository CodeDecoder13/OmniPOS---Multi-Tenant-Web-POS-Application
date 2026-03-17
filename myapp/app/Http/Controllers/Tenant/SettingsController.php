<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        return Inertia::render('tenant/settings/Index', [
            'settings' => $tenant->data ?? [],
        ]);
    }

    public function update(SettingsRequest $request, string $tenantSlug): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        $tenant->update([
            'data' => array_merge($tenant->data ?? [], $request->validated()),
        ]);

        return redirect()
            ->route('tenant.settings.edit', ['tenant' => $tenant->slug])
            ->with('success', 'Settings saved successfully.');
    }
}
