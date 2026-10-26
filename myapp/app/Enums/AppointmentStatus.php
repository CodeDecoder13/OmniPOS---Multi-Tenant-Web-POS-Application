<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Booked = 'booked';
    case Arrived = 'arrived';
    case InService = 'in_service';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Booked => 'Booked',
            self::Arrived => 'Arrived',
            self::InService => 'In service',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::NoShow => 'No-show',
        };
    }

    /** Still waiting to be served: it can be edited, moved on, or checked out. */
    public function isOpen(): bool
    {
        return in_array($this, [self::Booked, self::Arrived, self::InService], true);
    }

    /** Takes up the staff member's time. Cancelled and no-show bookings free the slot again. */
    public function holdsTime(): bool
    {
        return ! in_array($this, [self::Cancelled, self::NoShow], true);
    }

    /**
     * Moves a person can make by hand. "Completed" is deliberately missing: an appointment only
     * becomes completed by checking it out, so a completed appointment always has a sale behind it.
     *
     * @return list<self>
     */
    public function nextSteps(): array
    {
        return match ($this) {
            self::Booked => [self::Arrived, self::InService, self::Cancelled, self::NoShow],
            self::Arrived => [self::InService, self::Cancelled, self::NoShow],
            self::InService => [self::Cancelled],
            default => [],
        };
    }
}
