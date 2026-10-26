<?php

namespace App\Support;

/**
 * The commission rules in one place, with no database, so they can be tested on their own.
 *
 * A line earns commission on what the shop actually kept for it:
 *   price of the line
 *   - anything refunded on that line
 *   - its share of the order-level discounts (shared out in proportion to the line's size)
 * Tax is never part of it. The rate is a percent (0 to 100).
 */
final class CommissionCalculator
{
    public static function netLine(float $lineSubtotal, float $refundedAmount, float $orderSubtotal, float $orderDiscounts): float
    {
        if ($orderSubtotal <= 0) {
            return 0.0;
        }

        $afterRefund = max(0.0, $lineSubtotal - $refundedAmount);
        $discountShare = min(1.0, max(0.0, $orderDiscounts / $orderSubtotal));

        return round($afterRefund * (1 - $discountShare), 2);
    }

    /**
     * Share a line's net amount between the main person and an assistant who takes a percent of it.
     *
     * @return array{0: float, 1: float} [main person's part, assistant's part]
     */
    public static function split(float $net, float $assistantSharePercent): array
    {
        $assistant = round($net * min(100.0, max(0.0, $assistantSharePercent)) / 100, 2);

        return [round($net - $assistant, 2), $assistant];
    }

    public static function commission(float $netAmount, float $ratePercent): float
    {
        $rate = min(100.0, max(0.0, $ratePercent));

        return round($netAmount * $rate / 100, 2);
    }
}
