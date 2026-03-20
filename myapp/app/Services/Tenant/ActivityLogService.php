<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use App\Models\Tenant\ActivityLog;
use Illuminate\Support\Collection;

class ActivityLogService
{
    public function log(
        Tenant $tenant,
        int $actorId,
        string $action,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $properties = null,
    ): ActivityLog {
        return ActivityLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $actorId,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'properties' => $properties,
            'created_at' => now(),
        ]);
    }

    public function list(
        Tenant $tenant,
        ?string $subjectType = null,
        ?int $subjectId = null,
        int $limit = 20,
    ): Collection {
        $query = ActivityLog::forTenant($tenant->id)
            ->with('actor:id,name')
            ->orderByDesc('created_at');

        if ($subjectType) {
            $query->where('subject_type', $subjectType);
        }

        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }

        return $query->limit($limit)->get();
    }
}
