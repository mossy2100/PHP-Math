<?php

declare(strict_types = 1);

namespace Galaxon\Math;

/**
 * Container for useful float-related methods.
 */
final class Double
{
    /**
     * Private constructor to prevent instantiation.
     */
    private function __construct()
    {
    }

    /**
     * Determines if a floating-point number is negative zero (-0.0).
     *
     * In IEEE-754 floating-point arithmetic, negative zero is a distinct value from positive zero, though they compare
     * as equal. This method provides a way to distinguish between them.
     *
     * The method works by dividing 1.0 by the value. For negative zero, this division results in -INF.
     *
     * @param float $value The floating-point number to check.
     * @return bool True if the value is negative zero (-0.0), false otherwise.
     */
    public static function isNegativeZero(float $value): bool
    {
        // Using fdiv() to avoid a division by zero error.
        return $value === 0.0 && fdiv(1.0, $value) === -INF;
    }

    /**
     * Determines if a floating-point number is positive zero (+0.0).
     *
     * In IEEE-754 floating-point arithmetic, positive zero is a distinct value from negative zero, though they compare
     * as equal. This method provides a way to distinguish between them.
     *
     * The method works by dividing 1.0 by the value. For positive zero, this division results in INF.
     *
     * @param float $value The floating-point number to check.
     * @return bool True if the value is positive zero (+0.0), false otherwise.
     */
    public static function isPositiveZero(float $value): bool
    {
        // Using fdiv() to avoid a division by zero error.
        return $value === 0.0 && fdiv(1.0, $value) === INF;
    }

    /**
     * Normalize negative zero to positive zero. This can be used to avoid surprising results from certain operations.
     *
     * @param float $value The floating-point number to normalize.
     * @return float The normalized floating-point number.
     */
    public static function normalizeZero(float $value): float
    {
        return self::isNegativeZero($value) ? 0.0 : $value;
    }

    /**
     * Check if a number is negative.
     *
     * This method returns:
     * - true for -0.0, -INF, and negative values
     * - false for +0.0, INF, NaN, and positive values
     *
     * @param float $value The value to check.
     * @return bool True if the value is negative, false otherwise.
     */
    public static function isNegative(float $value): bool
    {
        return !is_nan($value) && ($value < 0 || self::isNegativeZero($value));
    }

    /**
     * Check if a number is positive.
     *
     * This method returns:
     * - true for +0.0, INF, and positive values
     * - false for -0.0, -INF, NaN, and negative values
     *
     * @param float $value The value to check.
     * @return bool True if the value is positive, false otherwise.
     */
    public static function isPositive(float $value): bool
    {
        return !is_nan($value) && ($value > 0 || self::isPositiveZero($value));
    }

    /**
     * Check if a float is one of the special values: NaN, -0.0, +INF, -INF.
     * +0.0 is not considered a special value.
     *
     * @param float $value The value to check.
     * @return bool True if the value is a special value, false otherwise.
     */
    public static function isSpecial(float $value): bool
    {
        return !is_finite($value) || self::isNegativeZero($value);
    }

    /**
     * Convert a float to a hexadecimal string.
     *
     * The advantage of this method over toString() is that every possible float value will produce a unique
     * 16-character string.
     * Whereas, with a cast to string, or sprintf(), the same string may be produced for different values.
     *
     * @param float $value The float to convert.
     * @return string The hexadecimal string representation of the float.
     */
    public static function toHex(float $value): string
    {
        return bin2hex(pack('d', $value));
    }

    /**
     * Normalize a scalar angle value into a specified half-open interval.
     *
     * If $signed is false (default), the range is [0, $units_per_turn).
     * If $signed is true, the range is [-$units_per_turn/2, $units_per_turn/2).
     *
     * @param float $value The value to wrap.
     * @param float $units_per_turn Units per full turn (e.g., TAU for radians, 360 for degrees, 400 for gradians).
     * @param bool $signed Whether to return a signed range instead of the default positive range.
     * @return float The wrapped value.
     */
    public static function wrap(float $value, float $units_per_turn, bool $signed = false): float
    {
        // Reduce using fmod to avoid large magnitudes.
        $r = fmod($value, $units_per_turn);

        // Get the range bounds.
        if ($signed) {
            $half = $units_per_turn / 2.0;
            $min = -$half;
            $max = $half;
        }
        else {
            $min = 0.0;
            $max = $units_per_turn;
        }

        // The value may be outside the range due to the sign of $value or the value of $signed.
        // Adjust accordingly.
        if ($r < $min) {
            $r += $units_per_turn;
        }
        elseif ($r >= $max) {
            $r -= $units_per_turn;
        }

        // Canonicalize -0.0 to 0.0.
        return self::normalizeZero($r);
    }
}
