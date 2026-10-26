<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\BusinessType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ChangeBusinessTypeRequest;
use App\Http\Requests\Tenant\SettingsRequest;
use App\Services\Tenant\ActivityLogService;
use App\Services\Tenant\CategoryService;
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
            'businessType' => $tenant->business_type->value,
            'businessTypes' => BusinessType::options(),
            'isOwner' => $tenant->owner_id === $request->user()->id,
        ]);
    }

    public function changeBusinessType(
        ChangeBusinessTypeRequest $request,
        string $tenantSlug,
        CategoryService $categories,
        ActivityLogService $log,
    ): RedirectResponse {
        $tenant = $request->attributes->get('current_tenant');

        abort_unless($tenant->owner_id === $request->user()->id, 403, 'Only the business owner can change the business type.');

        $new = BusinessType::from($request->validated('business_type'));

        if ($new !== $tenant->business_type) {
            $old = $tenant->business_type;
            try {
                $categories->resetForBusinessType($tenant, $new);
            } catch (\DomainException $e) {
                return back()->with('error', $e->getMessage());
            }
            $log->log($tenant, $request->user()->id, 'business_type.changed', null, null, [
                'from' => $old->value,
                'to' => $new->value,
            ]);
        }

        return redirect()
            ->route('tenant.settings.edit', ['tenant' => $tenant->slug])
            ->with('success', 'Business type changed. Products and categories were reset.');
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
