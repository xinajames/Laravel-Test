<?php

namespace App\Traits;

trait VbaRounding
{
    /**
     * VBA-style rounding with banker's rounding for exactly 0.5 values
     *
     * @param  float  $value  The value to round
     * @param  int  $precision  Number of decimal places (default: 0)
     * @return float The rounded value
     */
    protected function vbaRound(float $value, int $precision = 0): float
    {
        $factor = pow(10, $precision);
        $multiplied = $value * $factor;

        // Get the integer and fractional parts
        $floor = floor($multiplied);
        $fractionalPart = $multiplied - $floor;

        // VBA-style comparison: no epsilon tolerance
        if ($fractionalPart < 0.5) {
            // Round down
            return $floor / $factor;
        } elseif ($fractionalPart > 0.5) {
            // Round up
            return ($floor + 1) / $factor;
        } else {
            // Exactly 0.5 - VBA uses banker's rounding (round to even)
            if ($floor % 2 == 0) {
                return $floor / $factor;      // Even: round down
            } else {
                return ($floor + 1) / $factor; // Odd: round up
            }
        }
    }
}
