<?php

namespace App\Listeners;

use App\Events\ShiftOpened;
use App\Mail\ShiftStartedMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendShiftStartedEmail implements ShouldQueue
{
    public function handle(ShiftOpened $event): void
    {
        $shift = $event->shift;
        $shift->loadMissing(['tenant', 'operator:id,name', 'branch:id,name']);

        $tenant = $shift->tenant;
        $owner = User::find($tenant->owner_id);

        if (! $owner || ! $owner->email) {
            return;
        }

        $openedAt = Carbon::parse($shift->opened_at);

        Mail::to($owner->email)->send(new ShiftStartedMail([
            'ownerName' => $owner->name,
            'tenantName' => $tenant->name,
            'operatorName' => $shift->operator->name ?? 'Unknown',
            'branchName' => $shift->branch->name ?? 'Unknown',
            'openedAt' => $openedAt->format('M d, Y h:i A'),
            'startingCash' => round((float) $shift->starting_cash, 2),
        ]));
    }
}
