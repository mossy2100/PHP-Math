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
     * Normalize negative zero to positive zero.
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
     * Convert a float to a string that can't be confused with an integer, or with another float with a different value.
     *
     * Due to lack of precision in the way PHP converts floats to strings, two floats can have different values yet
     * have the same representation produced by a (string) cast. This function avoids that problem.
     *
     * @param float $value The float to convert.
     * @return string The string representation of the float.
     */
    public static function toString(float $value): string
    {
        // Handle special values.
        if (is_nan($value)) {
            return 'NaN';
        }

        if ($value === INF) {
            return '∞';
        }

        if ($value === -INF) {
            return '-∞';
        }

        // Convert the float to a string showing maximum useful precision.
        $s = sprintf('%.17g', $value);
        // If the string representation of the float value has no decimal point or exponent (i.e. nothing to distinguish
        // it from an integer), append a decimal point and a zero.
        if (!preg_match('/[.eE]/', $s)) {
            $s .= '.0';
        }
        return $s;
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
        $half = $units_per_turn / 2.0;
        $min = $signed ? -$half : 0.0;
        $max = $signed ? $half : $units_per_turn;

        // Adjust into the half-open interval [min, max).
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
