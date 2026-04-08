<?php

namespace App\Services\Tenant;

use App\Enums\OrderStatus;
use App\Models\Tenant;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CustomerService
{
    public function list(Tenant $tenant, Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = Customer::forTenant($tenant)
            ->withCount('orders');

        if ($search = $request->input('search')) {
            $query->search($search);
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function findForTenant(Tenant $tenant, int $customerId): Customer
    {
        return Customer::forTenant($tenant)->findOrFail($customerId);
    }

    public function create(Tenant $tenant, array $data, int $userId): Customer
    {
        return Customer::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'created_by' => $userId,
        ]);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);

        return $customer->fresh();
    }

    public function delete(Customer $customer): void
    {
        if ($customer->orders()->exists()) {
            throw new DomainException('Cannot delete a customer with existing orders. Consider deactivating instead.');
        }

        $customer->delete();
    }

    public function getStats(Customer $customer): array
    {
        $orders = $customer->orders()->where('status', OrderStatus::Completed);

        $totalOrders = $orders->count();
        $totalSpent = (float) $orders->sum('total');
        $avgOrder = $totalOrders > 0 ? round($totalSpent / $totalOrders, 2) : 0;
        $lastVisit = $orders->max('created_at');

        return [
            'total_orders' => $totalOrders,
            'total_spent' => $totalSpent,
            'avg_order_value' => $avgOrder,
            'last_visit' => $lastVisit,
        ];
    }

    public function getOrderHistory(Customer $customer, int $perPage = 15): LengthAwarePaginator
    {
        return $customer->orders()
            ->with(['creator:id,name', 'branch:id,name'])
            ->withCount('items')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function searchForPos(Tenant $tenant, ?string $search): Collection
    {
        $query = Customer::forTenant($tenant)
            ->where('is_active', true)
            ->select(['id', 'name', 'email', 'phone']);

        if ($search) {
            $query->search($search);
        }

        return $query->limit(10)->get();
    }
}
