<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\AppointmentRequest;
use App\Models\Tenant\Appointment;
use App\Models\Tenant\Product;
use App\Models\Tenant\StaffMember;
use App\Services\Tenant\ActivityLogService;
use App\Services\Tenant\AppointmentService;
use App\Support\BusinessTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentService $appointments,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function index(Request $request, string $tenantSlug): Response
    {
        $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);

        $tenant = $request->attributes->get('current_tenant');
        $branchId = $request->attributes->get('current_tenant_user')->branch_id ?? null;
        $date = $request->input('date', BusinessTime::today()->toDateString());

        return Inertia::render('tenant/appointments/Index', [
            'date' => $date,
            'today' => BusinessTime::today()->toDateString(),
            'appointments' => $this->appointments->forDay($tenant, $date, $branchId),
            'staff' => StaffMember::forTenant($tenant)->active()->orderBy('name')->get(['id', 'name']),
            // What can be booked: services first (they carry a duration), nothing that needs stock.
            'services' => Product::forTenant($tenant)->where('is_active', true)->where('is_service', true)
                ->orderBy('name')->get(['id', 'name', 'price', 'duration_minutes']),
        ]);
    }

    public function store(AppointmentRequest $request, string $tenantSlug): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $branchId = $request->attributes->get('current_tenant_user')->branch_id ?? null;

        $appointment = $this->appointments->create($tenant, $request->validated(), $request->user()->id, $branchId);

        $this->activityLog->log($tenant, $request->user()->id, 'appointment.created', 'Appointment', $appointment->id, ['customer' => $appointment->customer_name]);

        return back()->with('success', 'Appointment booked.');
    }

    public function update(AppointmentRequest $request, string $tenantSlug, int $appointment): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $model = Appointment::forTenant($tenant)->findOrFail($appointment);

        $this->appointments->update($tenant, $model, $request->validated());

        $this->activityLog->log($tenant, $request->user()->id, 'appointment.updated', 'Appointment', $model->id, ['customer' => $model->customer_name]);

        return back()->with('success', 'Appointment updated.');
    }

    public function status(Request $request, string $tenantSlug, int $appointment): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::enum(AppointmentStatus::class)]]);

        $tenant = $request->attributes->get('current_tenant');
        $model = Appointment::forTenant($tenant)->findOrFail($appointment);
        $to = AppointmentStatus::from($data['status']);

        $this->appointments->setStatus($tenant, $model, $to);

        $this->activityLog->log($tenant, $request->user()->id, 'appointment.status', 'Appointment', $model->id, ['status' => $to->value]);

        return back()->with('success', "Marked {$to->label()}.");
    }
}
