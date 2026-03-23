<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\Central\AdminActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        private AdminActivityLogService $activityLog,
    ) {}

    public function index(): Response
    {
        $settings = [
            'app_name' => SystemSetting::get('app_name', 'OmniPOS'),
            'registration_enabled' => SystemSetting::get('registration_enabled', '1'),
            'default_plan' => SystemSetting::get('default_plan', ''),
            'trial_days' => SystemSetting::get('trial_days', '14'),
            'support_email' => SystemSetting::get('support_email', ''),
            'currency_symbol' => SystemSetting::get('currency_symbol', '₱'),
        ];

        return Inertia::render('admin/settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:255'],
            'registration_enabled' => ['required', 'in:0,1'],
            'default_plan' => ['nullable', 'string'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'currency_symbol' => ['required', 'string', 'max:10'],
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::set($key, $value);
        }

        $this->activityLog->log(
            $request->user('admin'),
            'updated_settings',
            null,
            $validated,
            $request->ip(),
        );

        return back()->with('success', 'Settings updated successfully.');
    }
}
