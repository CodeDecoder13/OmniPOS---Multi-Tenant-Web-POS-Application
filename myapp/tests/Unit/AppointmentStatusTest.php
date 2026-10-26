<?php

namespace Tests\Unit;

use App\Enums\AppointmentStatus;
use PHPUnit\Framework\TestCase;

/** The booking rules that need no database. */
class AppointmentStatusTest extends TestCase
{
    public function test_completed_is_never_a_hand_move_so_it_always_has_a_sale_behind_it(): void
    {
        foreach (AppointmentStatus::cases() as $status) {
            $this->assertNotContains(AppointmentStatus::Completed, $status->nextSteps());
        }
    }

    public function test_finished_bookings_cannot_move_again(): void
    {
        foreach ([AppointmentStatus::Completed, AppointmentStatus::Cancelled, AppointmentStatus::NoShow] as $status) {
            $this->assertSame([], $status->nextSteps());
            $this->assertFalse($status->isOpen());
        }
    }

    public function test_cancelled_and_no_show_free_the_slot_but_the_rest_hold_it(): void
    {
        $this->assertFalse(AppointmentStatus::Cancelled->holdsTime());
        $this->assertFalse(AppointmentStatus::NoShow->holdsTime());
        $this->assertTrue(AppointmentStatus::Booked->holdsTime());
        $this->assertTrue(AppointmentStatus::Completed->holdsTime());
    }
}
