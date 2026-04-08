<?php

namespace App\Listeners;

use App\Events\LowStockReached;
use App\Models\Role;
use App\Models\TenantUser;
use App\Models\User;
use App\Notifications\LowStockNotification;

class SendLowStockDatabaseNotification
{
    public function handle(LowStockReached $event): void
    {
        $inventory = $event->inventory;
        $tenant = $event->tenant;

        $inventory->loadMissing(['product:id,name', 'branch:id,name']);

        $productName = $inventory->product->name ?? 'Unknown Product';
        $branchName = $inventory->branch->name ?? 'Unknown Branch';

        $notification = new LowStockNotification(
            $productName,
            $branchName,
            $inventory->quantity_on_hand,
            $inventory->low_stock_threshold,
        );

        // Notify tenant owner
        $owner = User::find($tenant->owner_id);
        if ($owner) {
            $owner->notify($notification);
        }

        // Notify branch managers/admins
        $managerRoleIds = Role::where('tenant_id', $tenant->id)
            ->whereIn('slug', ['admin', 'manager'])
            ->pluck('id');

        $branchUserIds = TenantUser::where('tenant_id', $tenant->id)
            ->where('branch_id', $inventory->branch_id)
            ->where('is_active', true)
            ->whereIn('role_id', $managerRoleIds)
            ->where('user_id', '!=', $tenant->owner_id)
            ->pluck('user_id');

        $branchUsers = User::whereIn('id', $branchUserIds)->get();

        foreach ($branchUsers as $user) {
            $user->notify($notification);
        }
    }
}
