<?php

declare(strict_types = 1);

namespace Galaxon\Math;

use ValueError;

/**
 * Container for general math utility methods.
 */
final class Math
{
    /**
     * Private constructor to prevent instantiation.
     */
    private function __construct()
    {
    }

    /**
     * Check if a value is a number, i.e. an integer or a float.
     * This varies from is_numeric(), which also returns true for numeric strings.
     *
     * @param mixed $value The value to check.
     * @return bool True if the value is a number, false otherwise.
     */
    public static function isNumber(mixed $value): bool
    {
        return is_int($value) || is_float($value);
    }

    /**
     * Check if a value is an unsigned integer.
     *
     * @param mixed $value The value to check.
     * @return bool True if the value is an unsigned integer, false otherwise.
     */
    public static function isUint(mixed $value): bool
    {
        return is_int($value) && $value >= 0;
    }

    /**
     * Get the sign of a number.
     *
     * @param int|float $value The number whose sign to check.
     * @param bool $zeroForZero If true (default), returns 0 for zero; otherwise, return the sign of the zero.
     * This will be -1 for float -0.0, or 1 for int 0 or float 0.0.
     * @return int 1 if the number is positive, -1 if negative, and 0, 1, or -1 if 0, depending on the second argument.
     */
    public static function sign(int|float $value, bool $zeroForZero = true): int
    {
        // Check for positive.
        if ($value > 0) {
            return 1;
        }

        // Check for negative.
        if ($value < 0) {
            return -1;
        }

        // Return result for 0.
        return $zeroForZero ? 0 : (Double::isNegativeZero($value) ? -1 : 1);
    }

    /**
     * Copy the sign of one number to another.
     *
     * @param int|float $num The number to copy the sign to.
     * @param int|float $sign_source The number to copy the sign from.
     * @return int|float The number with the sign of $sign_source.
     * @throws ValueError If NaN is passed as either parameter.
     */
    public static function copySign(int|float $num, int|float $sign_source): int|float
    {
        // Guard. This method won't work for NaN, which doesn't have a sign.
        if (is_nan($num) || is_nan($sign_source)) {
            throw new ValueError("NaN is not allowed for either parameter.");
        }

        return abs($num) * self::sign($sign_source, false);
    }
}
