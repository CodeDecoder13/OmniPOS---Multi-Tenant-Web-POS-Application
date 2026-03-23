<?php

namespace App\Services\Central;

use App\Models\Plan;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class PlanService
{
    public function list(): Collection
    {
        return Plan::withCount('subscriptions')->get();
    }

    public function create(array $data): Plan
    {
        $data['slug'] = Str::slug($data['name']);

        return Plan::create($data);
    }

    public function update(Plan $plan, array $data): Plan
    {
        $plan->update($data);

        return $plan->fresh();
    }

    public function delete(Plan $plan): void
    {
        if ($plan->subscriptions()->exists()) {
            throw new DomainException('Cannot delete a plan with active subscriptions.');
        }

        $plan->delete();
    }

    public function toggle(Plan $plan): Plan
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        return $plan;
    }
}
