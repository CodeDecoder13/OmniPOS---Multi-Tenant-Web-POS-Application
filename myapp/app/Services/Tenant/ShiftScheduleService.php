<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use App\Models\Tenant\ShiftSchedule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ShiftScheduleService
{
    public function list(
        Tenant $tenant,
        ?string $dayOfWeek = null,
        ?int $branchId = null,
        ?int $userId = null,
    ): LengthAwarePaginator {
        $query = ShiftSchedule::forTenant($tenant->id)
            ->with(['operator:id,name', 'branch:id,name', 'creator:id,name'])
            ->orderBy('start_time');

        if ($dayOfWeek) {
            $query->whereJsonContains('days_of_week', $dayOfWeek);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->paginate(20);
    }

    public function create(Tenant $tenant, array $data, int $createdBy): ShiftSchedule
    {
        return ShiftSchedule::create([
            'tenant_id' => $tenant->id,
            'user_id' => $data['user_id'],
            'branch_id' => $data['branch_id'] ?? null,
            'days_of_week' => $data['days_of_week'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'notes' => $data['notes'] ?? null,
            'created_by' => $createdBy,
        ]);
    }

    public function update(ShiftSchedule $schedule, array $data): ShiftSchedule
    {
        $schedule->update([
            'user_id' => $data['user_id'],
            'branch_id' => $data['branch_id'] ?? null,
            'days_of_week' => $data['days_of_week'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'notes' => $data['notes'] ?? null,
        ]);

        return $schedule;
    }

    public function delete(ShiftSchedule $schedule): void
    {
        $schedule->delete();
    }

    public function findForTenant(Tenant $tenant, int $id): ShiftSchedule
    {
        return ShiftSchedule::forTenant($tenant->id)->findOrFail($id);
    }
}
