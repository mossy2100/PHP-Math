<?php

declare(strict_types = 1);

namespace Galaxon\Math;

use OverflowException;
use ValueError;
use ArgumentCountError;

/**
 * Container for useful integer-related methods.
 */
final class Integer
{
    /**
     * Private constructor to prevent instantiation.
     */
    private function __construct()
    {
    }

    /**
     * Add two integers with overflow check.
     *
     * @param int $a The first integer.
     * @param int $b The second integer.
     * @return int The added integers if no overflow occurs.
     * @throws OverflowException If the addition results in overflow.
     */
    public static function add(int $a, int $b): int
    {
        // Do the addition.
        $c = $a + $b;

        // Check for overflow.
        if (is_float($c)) {
            throw new OverflowException("Overflow in integer addition.");
        }

        // Return the result.
        return $c;
    }

    /**
     * Subtract one integer from another with overflow check.
     *
     * @param int $a The first integer.
     * @param int $b The second integer.
     * @return int The result of subtracting the second integer from the first, if no overflow occurs.
     * @throws OverflowException If the subtraction results in overflow.
     */
    public static function sub(int $a, int $b): int
    {
        // Do the subtraction.
        $c = $a - $b;

        // Check for overflow.
        if (is_float($c)) {
            throw new OverflowException("Overflow in integer subtraction.");
        }

        // Return the result.
        return $c;
    }

    /**
     * Multiply two integers with overflow check.
     *
     * @param int $a The first integer.
     * @param int $b The second integer.
     * @return int The multiplied integers if no overflow occurs.
     * @throws OverflowException If the multiplication results in overflow.
     */
    public static function mul(int $a, int $b): int
    {
        // Do the multiplication.
        $c = $a * $b;

        // Check for overflow.
        if (is_float($c)) {
            throw new OverflowException("Overflow in integer multiplication.");
        }

        // Return the result.
        return $c;
    }

    /**
     * Raise one integer to the power of another with an overflow check.
     *
     * @param int $a The first integer.
     * @param int $b The second integer. Must be non-negative.
     * @return int The result integer if no overflow occurred.
     * @throws ValueError If $b is negative.
     * @throws OverflowException If the exponentiation results in overflow.
     */
    public static function pow(int $a, int $b): int
    {
        // Handle b < 0.
        if ($b < 0) {
            throw new ValueError("Negative exponents are not supported.");
        }

        // Do the exponentiation.
        $c = $a ** $b;

        // Check for overflow.
        if (is_float($c)) {
            throw new OverflowException("Overflow in exponentiation.");
        }

        // Return the result.
        return $c;
    }

    /**
     * Calculate the greatest common divisor of two or more integers.
     *
     * @param array $nums The integers to calculate the GCD of.
     * @return int The greatest common divisor.
     * @throws ArgumentCountError If no arguments are provided.
     */
    public static function gcd(int ...$nums): int
    {
        // Guard.
        if (count($nums) === 0) {
            throw new ArgumentCountError("At least one integer is required.");
        }

        // Initialise to the first number.
        $result = abs($nums[0]);

        // Calculate the GCD using Euclid's algorithm.
        for ($i = 1, $n = count($nums); $i < $n; $i++) {
            $a = $result;
            $b = abs($nums[$i]);

            while ($b !== 0) {
                $temp = $b;
                $b = $a % $b;
                $a = $temp;
            }

            $result = $a;
        }

        return $result;
    }
}
