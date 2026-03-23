<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\PlanRequest;
use App\Models\Plan;
use App\Services\Central\AdminActivityLogService;
use App\Services\Central\PlanService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function __construct(
        private PlanService $planService,
        private AdminActivityLogService $activityLog,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/plans/Index', [
            'plans' => $this->planService->list(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/plans/Create');
    }

    public function store(PlanRequest $request): RedirectResponse
    {
        $plan = $this->planService->create($request->validated());

        $this->activityLog->log(
            $request->user('admin'),
            'created_plan',
            $plan,
            ['name' => $plan->name],
            $request->ip(),
        );

        return redirect()->route('admin.plans.index')->with('success', "Plan {$plan->name} created successfully.");
    }

    public function edit(int $id): Response
    {
        $plan = Plan::findOrFail($id);

        return Inertia::render('admin/plans/Edit', [
            'plan' => $plan,
        ]);
    }

    public function update(PlanRequest $request, int $id): RedirectResponse
    {
        $plan = Plan::findOrFail($id);
        $plan = $this->planService->update($plan, $request->validated());

        $this->activityLog->log(
            $request->user('admin'),
            'updated_plan',
            $plan,
            ['name' => $plan->name],
            $request->ip(),
        );

        return redirect()->route('admin.plans.index')->with('success', "Plan {$plan->name} updated successfully.");
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $plan = Plan::findOrFail($id);
        $name = $plan->name;

        try {
            $this->planService->delete($plan);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->activityLog->log(
            $request->user('admin'),
            'deleted_plan',
            null,
            ['name' => $name],
            $request->ip(),
        );

        return redirect()->route('admin.plans.index')->with('success', "Plan {$name} deleted successfully.");
    }

    public function toggle(Request $request, int $id): RedirectResponse
    {
        $plan = Plan::findOrFail($id);
        $this->planService->toggle($plan);

        $this->activityLog->log(
            $request->user('admin'),
            $plan->is_active ? 'activated_plan' : 'deactivated_plan',
            $plan,
            ['name' => $plan->name],
            $request->ip(),
        );

        return back()->with('success', "Plan {$plan->name} " . ($plan->is_active ? 'activated' : 'deactivated') . '.');
    }
}
