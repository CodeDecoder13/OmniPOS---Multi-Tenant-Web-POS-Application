<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ReceiptCustomizationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ReceiptCustomizationController extends Controller
{
    public function edit(Request $request, string $tenantSlug): Response|RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        // Enterprise plan check
        $planSlug = $tenant->subscription?->plan?->slug;
        if ($planSlug !== 'enterprise') {
            return redirect()
                ->route('tenant.settings', $tenantSlug)
                ->with('error', 'Receipt customization requires an Enterprise plan.');
        }

        $settings = $tenant->data ?? [];

        // Add logo URL if logo path exists
        if (! empty($settings['receipt_logo'])) {
            $settings['receipt_logo_url'] = Storage::disk('public')->url($settings['receipt_logo']);
        }

        return Inertia::render('tenant/receipt-customization/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(ReceiptCustomizationRequest $request, string $tenantSlug): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        // Enterprise plan check
        $planSlug = $tenant->subscription?->plan?->slug;
        if ($planSlug !== 'enterprise') {
            return redirect()
                ->back()
                ->with('error', 'Receipt customization requires an Enterprise plan.');
        }

        $data = $tenant->data ?? [];

        // Handle logo upload
        if ($request->hasFile('receipt_logo')) {
            // Delete old logo if exists
            if (! empty($data['receipt_logo'])) {
                Storage::disk('public')->delete($data['receipt_logo']);
            }

            $path = $request->file('receipt_logo')->store(
                "tenants/{$tenant->id}/receipt",
                'public'
            );
            $data['receipt_logo'] = $path;
        }

        // Handle logo removal
        if ($request->boolean('remove_logo')) {
            if (! empty($data['receipt_logo'])) {
                Storage::disk('public')->delete($data['receipt_logo']);
            }
            $data['receipt_logo'] = null;
        }

        // Update receipt settings
        $fields = [
            'receipt_header',
            'receipt_footer',
            'receipt_thank_you_message',
            'receipt_show_address',
            'receipt_show_phone',
            'receipt_show_customer',
            'receipt_show_table',
            'receipt_show_order_type',
            'receipt_show_tax_breakdown',
            'receipt_width',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->input($field);
            }
        }

        $tenant->update(['data' => $data]);

        return redirect()
            ->route('tenant.receipt-customization.edit', ['tenant' => $tenant->slug])
            ->with('success', 'Receipt design saved successfully.');
    }
}
