<?php

namespace App\Services\Tenant;

use App\Enums\AppointmentStatus;
use App\Models\Tenant;
use App\Models\Tenant\Appointment;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Support\BusinessTime;
use App\Support\TenantLock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    /** Used when the chosen services have no duration set. */
    private const DEFAULT_MINUTES = 30;

    /** One local day of bookings, with the minutes-from-midnight the screen needs. */
    public function forDay(Tenant $tenant, string $date, ?int $branchId): array
    {
        $query = Appointment::forTenant($tenant)
            ->whereBetween('starts_at', [BusinessTime::startOfDay($date), BusinessTime::endOfDay($date)])
            ->with(['items', 'staff:id,name'])
            ->orderBy('starts_at');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get()->map(fn (Appointment $a) => $this->present($a))->all();
    }

    public function present(Appointment $a): array
    {
        $start = $a->starts_at->copy()->setTimezone(BusinessTime::tz());
        $end = $a->ends_at->copy()->setTimezone(BusinessTime::tz());

        return [
            'id' => $a->id,
            'customer_name' => $a->customer_name,
            'customer_phone' => $a->customer_phone,
            'staff_member_id' => $a->staff_member_id,
            'staff_name' => $a->staff?->name,
            'date' => $start->toDateString(),
            'time' => $start->format('H:i'),
            'start_label' => $start->format('g:i A'),
            'end_label' => $end->format('g:i A'),
            'status' => $a->status->value,
            'status_label' => $a->status->label(),
            'next_steps' => array_map(fn ($s) => ['value' => $s->value, 'label' => $s->label()], $a->status->nextSteps()),
            'can_edit' => $a->status->isOpen(),
            'notes' => $a->notes,
            'order_id' => $a->order_id,
            'product_ids' => $a->items->pluck('product_id')->filter()->values(),
            'items' => $a->items->map(fn ($i) => ['name' => $i->name, 'price' => (float) $i->price, 'duration_minutes' => $i->duration_minutes]),
            'total' => (float) $a->items->sum('price'),
        ];
    }

    public function create(Tenant $tenant, array $data, int $userId, ?int $branchId): Appointment
    {
        return DB::transaction(function () use ($tenant, $data, $userId, $branchId) {
            [$items, $start, $end] = $this->resolve($tenant, $data);
            $this->assertFree($tenant, (int) $data['staff_member_id'], $start, $end);

            $appointment = Appointment::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branchId,
                'staff_member_id' => $data['staff_member_id'],
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'starts_at' => $start,
                'ends_at' => $end,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);
            $appointment->items()->createMany($items);

            return $appointment;
        });
    }

    public function update(Tenant $tenant, Appointment $appointment, array $data): Appointment
    {
        return DB::transaction(function () use ($tenant, $appointment, $data) {
            $appointment = Appointment::forTenant($tenant)->lockForUpdate()->findOrFail($appointment->id);
            $this->assertOpen($appointment);

            [$items, $start, $end] = $this->resolve($tenant, $data);
            $this->assertFree($tenant, (int) $data['staff_member_id'], $start, $end, $appointment->id);

            $appointment->update([
                'staff_member_id' => $data['staff_member_id'],
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'starts_at' => $start,
                'ends_at' => $end,
                'notes' => $data['notes'] ?? null,
            ]);
            $appointment->items()->delete();
            $appointment->items()->createMany($items);

            return $appointment;
        });
    }

    /** Hand moves only. "Completed" comes from checking out, never from here. */
    public function setStatus(Tenant $tenant, Appointment $appointment, AppointmentStatus $to): Appointment
    {
        return DB::transaction(function () use ($tenant, $appointment, $to) {
            $appointment = Appointment::forTenant($tenant)->lockForUpdate()->findOrFail($appointment->id);

            if (! in_array($to, $appointment->status->nextSteps(), true)) {
                throw ValidationException::withMessages([
                    'status' => "A {$appointment->status->label()} appointment cannot be set to {$to->label()}.",
                ]);
            }

            $appointment->update(['status' => $to]);

            return $appointment;
        });
    }

    /**
     * Called inside the checkout transaction: the sale is what completes the appointment.
     * The row is locked so two tills cannot check out the same booking.
     */
    public function completeWithOrder(Tenant $tenant, int $appointmentId, Order $order): void
    {
        $appointment = Appointment::forTenant($tenant)->lockForUpdate()->find($appointmentId);

        if (! $appointment) {
            throw ValidationException::withMessages(['appointment_id' => 'That appointment no longer exists.']);
        }

        $this->assertOpen($appointment);
        $appointment->update(['status' => AppointmentStatus::Completed, 'order_id' => $order->id]);
    }

    private function assertOpen(Appointment $appointment): void
    {
        if (! $appointment->status->isOpen()) {
            throw ValidationException::withMessages([
                'appointment' => "This appointment is already {$appointment->status->label()}.",
            ]);
        }
    }

    /** @return array{0: array, 1: Carbon, 2: Carbon} item rows, start (UTC), end (UTC) */
    private function resolve(Tenant $tenant, array $data): array
    {
        $products = Product::forTenant($tenant)->whereIn('id', $data['product_ids'])->get()->keyBy('id');

        // Snapshot name, price and length, in the order the customer will get them.
        $items = collect($data['product_ids'])->map(fn ($id) => [
            'product_id' => $products[$id]->id,
            'name' => $products[$id]->name,
            'price' => $products[$id]->price,
            'duration_minutes' => (int) $products[$id]->duration_minutes,
        ])->all();

        $minutes = array_sum(array_column($items, 'duration_minutes')) ?: self::DEFAULT_MINUTES;
        $start = Carbon::parse("{$data['date']} {$data['time']}", BusinessTime::tz())->utc();

        return [$items, $start, $start->copy()->addMinutes($minutes)];
    }

    /** No double-booking one person. The lock makes two simultaneous requests take turns. */
    private function assertFree(Tenant $tenant, int $staffId, Carbon $start, Carbon $end, ?int $exceptId = null): void
    {
        TenantLock::acquire($tenant->id, "appointments-{$staffId}");

        $busy = Appointment::forTenant($tenant)
            ->where('staff_member_id', $staffId)
            ->whereNotIn('status', [AppointmentStatus::Cancelled->value, AppointmentStatus::NoShow->value])
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->first();

        if ($busy) {
            $from = $busy->starts_at->copy()->setTimezone(BusinessTime::tz())->format('g:i A');
            $to = $busy->ends_at->copy()->setTimezone(BusinessTime::tz())->format('g:i A');

            throw ValidationException::withMessages(['time' => "That staff member is already booked {$from} to {$to}."]);
        }
    }
}
