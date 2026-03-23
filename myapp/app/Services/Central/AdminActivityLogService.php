<?php

namespace App\Services\Central;

use App\Models\Admin;
use App\Models\AdminActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AdminActivityLogService
{
    public function log(Admin $admin, string $action, ?Model $subject = null, ?array $properties = null, ?string $ip = null): AdminActivityLog
    {
        return AdminActivityLog::create([
            'admin_id' => $admin->id,
            'action' => $action,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject ? $subject->getKey() : null,
            'properties' => $properties,
            'ip_address' => $ip,
        ]);
    }

    public function getRecent(int $limit = 10): Collection
    {
        return AdminActivityLog::with('admin:id,name')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    public function getPaginated(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = AdminActivityLog::with('admin:id,name')
            ->latest('created_at');

        if (! empty($filters['admin_id'])) {
            $query->where('admin_id', $filters['admin_id']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', 'like', "%{$filters['action']}%");
        }

        if (! empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
