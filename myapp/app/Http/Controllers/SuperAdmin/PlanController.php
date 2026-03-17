<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function index(): Response
    {
        $plans = Plan::withCount(['subscriptions'])
            ->get();

        return Inertia::render('admin/plans/Index', [
            'plans' => $plans,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $plan = Plan::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'max_branches' => ['nullable', 'integer', 'min:1'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_products' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $plan->update($validated);

        return back()->with('success', "Plan {$plan->name} updated successfully.");
    }
}
