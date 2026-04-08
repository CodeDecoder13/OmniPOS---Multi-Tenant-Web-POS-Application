<?php

namespace App\Console\Commands;

use App\Enums\ShiftStatus;
use App\Models\Tenant\Shift;
use App\Services\Tenant\ShiftService;
use Illuminate\Console\Command;

class CloseStaleShifts extends Command
{
    protected $signature = 'shifts:close-stale {--hours=12 : Hours after which an open shift is considered stale}';

    protected $description = 'Auto-close shifts that have been open longer than the specified hours';

    public function handle(ShiftService $shiftService): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        $staleShifts = Shift::where('status', ShiftStatus::Open)
            ->where('opened_at', '<', $cutoff)
            ->get();

        if ($staleShifts->isEmpty()) {
            $this->info('No stale shifts found.');

            return self::SUCCESS;
        }

        foreach ($staleShifts as $shift) {
            $shiftService->closeShift($shift, [
                'ending_cash' => 0,
                'notes' => "Auto-closed: shift was open for more than {$hours} hours.",
            ]);

            $this->line("Closed shift #{$shift->id} (opened at {$shift->opened_at})");
        }

        $this->info("Closed {$staleShifts->count()} stale shift(s).");

        return self::SUCCESS;
    }
}
