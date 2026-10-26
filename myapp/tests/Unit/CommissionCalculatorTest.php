<?php

namespace Tests\Unit;

use App\Support\CommissionCalculator;
use PHPUnit\Framework\TestCase;

/** No database needed: the commission rules are plain arithmetic. */
class CommissionCalculatorTest extends TestCase
{
    public function test_pays_commission_on_the_line_price_when_nothing_is_refunded_or_discounted(): void
    {
        $net = CommissionCalculator::netLine(800, 0, 800, 0);

        $this->assertSame(800.0, $net);
        $this->assertSame(160.0, CommissionCalculator::commission($net, 20));
    }

    public function test_takes_a_refund_off_the_line_before_commission(): void
    {
        $net = CommissionCalculator::netLine(800, 300, 800, 0);

        $this->assertSame(500.0, $net);
        $this->assertSame(50.0, CommissionCalculator::commission($net, 10));
    }

    public function test_shares_an_order_discount_across_lines_in_proportion_to_their_size(): void
    {
        // Order of 1000 (an 800 service plus a 200 service) with a 100 discount: 10% off every line.
        $this->assertSame(720.0, CommissionCalculator::netLine(800, 0, 1000, 100));
        $this->assertSame(180.0, CommissionCalculator::netLine(200, 0, 1000, 100));
    }

    public function test_never_goes_negative_or_pays_more_than_one_hundred_percent(): void
    {
        $this->assertSame(0.0, CommissionCalculator::netLine(100, 500, 100, 0));
        $this->assertSame(0.0, CommissionCalculator::netLine(100, 0, 100, 999));
        $this->assertSame(0.0, CommissionCalculator::netLine(100, 0, 0, 0));
        $this->assertSame(100.0, CommissionCalculator::commission(100, 150));
        $this->assertSame(0.0, CommissionCalculator::commission(100, -5));
    }
}
