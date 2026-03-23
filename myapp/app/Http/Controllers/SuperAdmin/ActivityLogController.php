<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Central\AdminActivityLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function __construct(
        private AdminActivityLogService $activityLogService,
    ) {}

    public function index(Request $request): Response
    {
        $logs = $this->activityLogService->getPaginated(
            $request->only(['admin_id', 'action', 'subject_type', 'date_from', 'date_to']),
        );

        return Inertia::render('admin/activity-log/Index', [
            'logs' => $logs,
            'filters' => $request->only(['admin_id', 'action', 'subject_type', 'date_from', 'date_to']),
            'admins' => Admin::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
