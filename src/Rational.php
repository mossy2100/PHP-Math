<?php

declare(strict_types = 1);

namespace Galaxon\Math;

use DomainException;
use OverflowException;
use Override;
use RangeException;
use Stringable;
use Galaxon\Math\{Math, Integer};

/**
 * A rational number, represented as a ratio of two PHP integers, signifying the numerator and denominator.
 *
 * These values are maintained in a canonical form:
 * - 0 is represented as 0/1.
 * - The denominator is always positive. Thus, the sign of the rational is stored as the sign of the numerator.
 * - The fraction is reduced to its simplest form (e.g. 9/12 will be automatically reduced to 3/4).
 *
 * NB: The valid range of the absolute value of a Rational is 1/PHP_INT_MAX to PHP_INT_MAX/1.
 * Therefore, neither the numerator nor the denominator can be PHP_INT_MIN.
 * The reason is that PHP_INT_MIN equals -(PHP_INT_MAX + 1), i.e. it can't be negated without overflowing.
 * Allowing the numerator or the denominator to equal PHP_INT_MIN therefore complicates negation, reciprocal,
 * subtraction, and simplification methods.
 * So, while it's technically possible, supporting this edge case inflates the code for little gain.
 */
final class Rational implements Stringable
{
    // region Properties

    /**
     * The numerator.
     *
     * @var int
     */
    private(set) int $num;

    /**
     * The denominator.
     *
     * @var int
     */
    private(set) int $den;

    // endregion

    // region Constructor

    /**
     * Constructor.
     *
     * @param int $num The numerator. Defaults to 0.
     * @param int $den The denominator. Defaults to 1.
     */
    public function __construct(int $num = 0, int $den = 1)
    {
        // Check for zero denominator.
        if ($den === 0) {
            throw new DomainException('Denominator cannot be zero.');
        }

        // Simplify the fraction.
        [$num, $den] = self::simplify($num, $den);

        // Set the properties.
        $this->num = $num;
        $this->den = $den;
    }

    // endregion

    // region Factory methods

    /**
     * Create a rational number from a real number using continued fractions.
     * This finds the simplest rational that equals the provided number (or is as close as is practical).
     *
     * If an exact match is not found, the method will return the closest approximation with a denominator less than
     * or equal to $max_den. This is likely to be a more useful result than an exception and limits the time spent
     * in the method.
     *
     * The valid range for the absolute value of a Rational is 1/PHP_INT_MAX to PHP_INT_MAX/1.
     * This method will throw an exception if the given value is outside that range.
     *
     * If you find the method to be slow in your environment, reduce the value of $max_den, but it should be OK.
     * In development the method runs superfast, but that was done on a high-end gaming laptop.
     * Tests indicate a $max_den of 200 million is sufficient for exact round-trip conversion between float and
     * Rational for e, pi, tau, and common square roots and fractions.
     *
     * Float representation limits can cause inexact round-trip conversions for values very close to integers.
     *
     * This method accepts ints as well as floats because most integers larger than 2^53 cannot be represented
     * exactly as floats, and we want it to work correctly with those numbers, too. Float conversion would cause a
     * loss of precision. This problem only exists on a 64-bit platform where integer magnitude is up to 2^63.
     *
     * @param float|int $value The real number value.
     * @return self The equivalent rational number.
     * @throws DomainException If the value is infinite or NaN.
     * @throws RangeException If the value is outside the valid convertible range.
     */
    public static function fromNumber(float|int $value): self
    {
        // Check for infinite or NaN.
        if (!is_finite($value)) {
            throw new DomainException('Cannot convert an infinity or NaN to a rational number.');
        }

        // Shortcut. Handle integers.
        if ((is_int($value) || $value === (float)(int)$value) && $value > PHP_INT_MIN) {
            return new self((int)$value);
        }

        // Set up.
        $max_den = PHP_INT_MAX;
        $sign = Math::sign($value, false);
        $abs_value = abs($value);

        // Check for values outside the valid range.
        if ($abs_value < 1 / PHP_INT_MAX || $abs_value > PHP_INT_MAX) {
            throw new RangeException('The value is outside the valid range for representation as a rational number.');
        }

        // Initialize convergents.
        $h0 = 1;
        $h1 = 0;
        $k0 = 0;
        $k1 = 1;

        // Set the initial approximation.
        $x = (float)$abs_value;

        // Track the best approximation found so far.
        $h_best = $abs_value < 0.5 ? 0 : 1;
        $k_best = 1;
        $min_err = (float)abs($h_best - $abs_value);

        // Loop until done.
        while (true) {
            // Extract integer part.
            $a = (int)floor($x);

            // Calculate next convergent
            $h_new = $a * $h0 + $h1;
            $k_new = $a * $k0 + $k1;

            // If denominator exceeds limit, return the best approximation found so far.
            if ($k_new > $max_den) {
                return new self($sign * $h_best, $k_best);
            }

            // Check if we've found an exact representation.
            $err = (float)abs($h_new / $k_new - $abs_value);
            if ($err === 0.0) {
                return new self($sign * $h_new, $k_new);
            }

            // Check if this convergent is better than the best so far.
            if ($err < $min_err) {
                $h_best = $h_new;
                $k_best = $k_new;
                $min_err = $err;
            }

            // Update convergents.
            $h1 = $h0;
            $h0 = $h_new;
            $k1 = $k0;
            $k0 = $k_new;

            // Calculate remainder.
            $rem = $x - $a;

            // If the remainder is 0, we're done.
            if ($rem === 0.0) {
                return new self($sign * $h0, $k0);
            }

            // Calculate next approximation.
            $x = 1.0 / $rem;
        }
    }

    /**
     * Parse a string into a rational number.
     *
     * It will accept string values of the following form:
     * - int, e.g. "123", "-456"
     * - float, e.g. "123.456", "-456.789"
     * - fraction, e.g. "1/2", "-3/4"
     *
     * If the string represents a float, it will be converted to the closest rational number if its within the valid
     * range.
     *
     * The input string is trimmed, including fraction parts. Therefore, the following examples are all allowed:
     * - " 123", "-456 ", etc.
     * - " 123.456", "-456.789 ", etc.
     * - " 1/2", "-3/4 ", " 5 / 6", etc.
     *
     * @param string $s The string to parse.
     * @return self The parsed rational number.
     * @throws DomainException If the string cannot be parsed into a rational number.
     * @throws RangeException If the string represents a number that is outside the valid convertible range.
     */
    public static function parse(string $s): self
    {
        // Check for string that looks like an integer.
        $n = filter_var($s, FILTER_VALIDATE_INT);
        if (is_int($n)) {
            if ($n === PHP_INT_MIN) {
                throw new RangeException('The value is outside the valid range for representation as a rational number.');
            }
            return new self($n);
        }

        // Check for string that looks like a float.
        $n = filter_var($s, FILTER_VALIDATE_FLOAT);
        if (is_float($n)) {
            return self::fromNumber($n);
        }

        // Check for string that looks like a fraction (int/int).
        $parts = explode('/', $s);
        if (count($parts) === 2) {
            $n = filter_var($parts[0], FILTER_VALIDATE_INT);
            $d = filter_var($parts[1], FILTER_VALIDATE_INT);
            if (is_int($n) && is_int($d)) {
                return new self($n, $d);
            }
        }

        throw new DomainException("Invalid rational number: $s");
    }

    /**
     * Convert a number or string into a Rational, if it isn't one already.
     *
     * This serves as a helper method used by many of the arithmetic methods in this class, but may have utility
     * as a general-purpose conversion method elsewhere.
     *
     * @param int|float|string|self $value The number to convert.
     * @return self The equivalent Rational.
     * @throws DomainException If the number is NaN or infinite, or if the input string does not represent a valid rational.
     * @throws RangeException If the value is outside the valid convertible range.
     */
    public static function toRational(int|float|string|self $value): self
    {
        // Check for Rational.
        if ($value instanceof self) {
            return $value;
        }

        // Check for string.
        if  (is_string($value)) {
            return self::parse($value);
        }

        // Must be int or float.
        return self::fromNumber($value);
    }

    // endregion

    // region Conversion methods

    /**
     * Convert the rational number to a float.
     *
     * @return float The equivalent float.
     */
    public function toFloat(): float
    {
        return $this->num / $this->den;
    }

    /**
     * Convert the rational number to an int.
     *
     * @return int The closest integer, rounding towards zero.
     */
    public function toInt(): int
    {
        return intdiv($this->num, $this->den);
    }

    /**
     * Convert the rational number to a string. (Stringable implementation.)
     *
     * @return string The string representation of the rational number.
     */
    #[Override]
    public function __toString(): string
    {
        return $this->num . ($this->den === 1 ? '' : '/' . $this->den);
    }

    // endregion

    // region Arithmetic operations

    /**
     * Calculate the negative of this rational number.
     *
     * @return self A new rational number representing the negative.
     */
    public function neg(): self
    {
        return new self(-$this->num, $this->den);
    }

    /**
     * Add another value to this rational number.
     *
     * @param int|float|self $other The value to add.
     * @return self A new rational number representing the sum.
     * @throws OverflowException If the result overflows an integer.
     */
    public function add(int|float|self $other): self
    {
        $other = self::toRational($other);

        // (a/b) + (c/d) = (ad + bc) / (bd)
        $f = Integer::mul($this->num, $other->den);
        $g = Integer::mul($this->den, $other->num);
        $h = Integer::add($f, $g);
        $k = Integer::mul($this->den, $other->den);

        return new self($h, $k);
    }

    /**
     * Subtract another value from this rational number.
     *
     * @param int|float|self $other The value to subtract.
     * @return self A new rational number representing the difference.
     * @throws OverflowException If the result overflows an integer.
     */
    public function sub(int|float|self $other): self
    {
        $other = self::toRational($other);
        return $this->add($other->neg());
    }

    /**
     * Calculate the reciprocal of this rational number.
     *
     * @return self A new rational number representing the reciprocal.
     */
    public function inv(): self
    {
        // Guard.
        if ($this->num === 0) {
            throw new DomainException("Cannot take reciprocal of zero.");
        }

        // Preserve sign: if num is negative, swap and negate.
        return $this->num > 0
            ? new self($this->den, $this->num)
            : new self(-$this->den, -$this->num);
    }

    /**
     * Multiply this rational number by another value.
     *
     * @param int|float|self $other The value to multiply by.
     * @return self A new rational number representing the product.
     * @throws OverflowException If the result overflows an integer.
     */
    public function mul(int|float|self $other): self
    {
        $other = self::toRational($other);

        // Cross-cancel before multiplying: (a/b) * (c/d)
        // Cancel gcd(a,d) from a and d
        // Cancel gcd(b,c) from b and c
        $gcd1 = Integer::gcd($this->num, $other->den);
        $gcd2 = Integer::gcd($this->den, $other->num);

        $a = intdiv($this->num, $gcd1);
        $b = intdiv($this->den, $gcd2);
        $c = intdiv($other->num, $gcd2);
        $d = intdiv($other->den, $gcd1);

        // Now multiply the reduced terms: (a/b) * (c/d) = ac/bd
        $h = Integer::mul($a, $c);
        $k = Integer::mul($b, $d);

        return new self($h, $k);
    }

    /**
     * Divide this rational number by another value.
     *
     * @param int|float|self $other The value to divide by.
     * @return self A new rational number representing the quotient.
     * @throws DomainException If dividing by zero.
     * @throws OverflowException If the result overflows an integer.
     */
    public function div(int|float|self $other): self
    {
        // Guard.
        $other = self::toRational($other);
        if ($other->num === 0) {
            throw new DomainException("Cannot divide by zero.");
        }

        return $this->mul($other->inv());
    }

    /**
     * Raise this rational number to an integer power.
     *
     * @param int $exponent The integer exponent.
     * @return self A new rational number representing the result.
     * @throws DomainException If raising zero to a negative power.
     * @throws OverflowException If the result overflows an integer.
     */
    public function pow(int $exponent): self
    {
        // Any number to the power of 0 is 1, including 0.
        // 0^0 can be considered undefined, but many programming languages (including PHP) return 1.
        if ($exponent === 0) {
            return new self(1);
        }

        // Handle 0 base.
        if ($this->num === 0) {
            // 0 to the power of a negative exponent is invalid (effectively division by zero).
            if ($exponent < 0) {
                throw new DomainException("Cannot raise zero to a negative power.");
            }

            // 0 to the power of a positive exponent is 0.
            return new self(0);
        }

        // Handle negative exponents by taking reciprocal.
        if ($exponent < 0) {
            return $this->inv()->pow(-$exponent);
        }

        // Calculate the new numerator and denominator with overflow checks.
        $h = Integer::pow($this->num, $exponent);
        $k = Integer::pow($this->den, $exponent);

        // Return the result.
        return new self($h, $k);
    }

    /**
     * Calculate the absolute value of this rational number.
     *
     * @return self A new rational number representing the absolute value.
     */
    public function abs(): self
    {
        return new self(abs($this->num), $this->den);
    }

    /**
     * Find the closest integer less than or equal to the rational number.
     *
     * @return int The floored value.
     */
    public function floor(): int {
        if ($this->den === 1) {
            return $this->num;
        }
        $q = intdiv($this->num, $this->den);
        return $this->num < 0 ? $q - 1 : $q;
    }

    /**
     * Find the closest integer greater than or equal to the rational number.
     *
     * @return int The ceiling value.
     */
    public function ceil(): int {
        if ($this->den === 1) {
            return $this->num;
        }
        $q = intdiv($this->num, $this->den);
        return $this->num > 0 ? $q + 1 : $q;
    }

    /**
     * Find the integer closest to the rational number.
     *
     * The rounding method used here is "half away from zero", to match the default rounding mode used by PHP's
     * round() function. A future version of this method could include a RoundingMode parameter.
     *
     * @return int The closest integer.
     */
    public function round(): int {
        if ($this->den === 1) {
            return $this->num;
        }

        $q = intdiv($this->num, $this->den);
        $r = $this->num % $this->den;

        // Round away from zero if remainder ≥ half denominator.
        return (abs($r) * 2 >= $this->den) ? ($this->num > 0 ? $q + 1 : $q - 1) : $q;
    }

    // endregion

    // region Comparison methods

    /**
     * Compare a rational number with another number.
     *
     * @param int|float|self $other The number to compare with.
     * @return int Returns -1 if this < other, 0 if equal, 1 if this > other.
     */
    public function cmp(int|float|self $other): int
    {
        $other = self::toRational($other);

        // If denominators are equal, just compare numerators.
        if ($this->den === $other->den) {
            $left = $this->num;
            $right = $other->num;
        }
        else {
            try {
                // Cross multiply: compare a*d with b*c for a/b vs c/d.
                $left = Integer::mul($this->num, $other->den);
                $right = Integer::mul($this->den, $other->num);
            } catch (OverflowException) {
                // In case of overflow, compare equivalent floating point values.
                // NB: This could produce a result of 0 (equal) if two rationals that are actually different convert to
                // the same float, which is possible for values with a magnitude greater than or equal to 2^53 (64-bit
                // platforms only).
                $left = $this->toFloat();
                $right = $other->toFloat();
            }
        }

        // The spaceship operator's contract only guarantees sign, not specific values. Normalize to -1, 0, or 1 for
        // predictable behavior used by other comparison methods.
        return Math::sign($left <=> $right);
    }

    /**
     * Check if this rational number equals another number.
     *
     * @param int|float|self $other The number to compare with.
     * @return bool True if equal, false otherwise.
     */
    public function eq(int|float|self $other): bool
    {
        return $this->cmp($other) === 0;
    }

    /**
     * Check if this rational number is less than another number.
     *
     * @param int|float|self $other The number to compare with.
     * @return bool True if less than, false otherwise.
     */
    public function lt(int|float|self $other): bool
    {
        return $this->cmp($other) === -1;
    }

    /**
     * Check if this rational number is greater than another number.
     *
     * @param int|float|self $other The number to compare with.
     * @return bool True if greater than, false otherwise.
     */
    public function gt(int|float|self $other): bool
    {
        return $this->cmp($other) === 1;
    }

    /**
     * Check if this rational number is less than or equal to another number.
     *
     * @param int|float|self $other The number to compare with.
     * @return bool True if less than or equal to, false otherwise.
     */
    public function lte(int|float|self $other): bool
    {
        return $this->cmp($other) !== 1;
    }

    /**
     * Check if this rational number is greater than or equal to another number.
     *
     * @param int|float|self $other The number to compare with.
     * @return bool True if greater than or equal to, false otherwise.
     */
    public function gte(int|float|self $other): bool
    {
        return $this->cmp($other) !== -1;
    }

    // endregion

    // region Helper methods (private static)

    /**
     * Convert a fraction to its canonical form.
     *
     * @param int $num The numerator.
     * @param int $den The denominator.
     * @return int[] The simplified numerator and denominator.
     * @throws RangeException If the numerator or denominator equals PHP_INT_MIN.
     */
    private static function simplify(int $num, int $den): array
    {
        // Check for PHP_INT_MIN.
        if ($num === PHP_INT_MIN || $den === PHP_INT_MIN) {
            throw new RangeException("A numerator or denominator equal to PHP_INT_MIN (" .PHP_INT_MIN . ") is not supported.");
        }

        // Check for 0.
        if ($num === 0) {
            return [0, 1];
        }

        // Check for 1.
        if ($num === $den) {
            return [1, 1];
        }

        // Check for -1.
        if ($num === -$den) {
            return [-1, 1];
        }

        // Calculate the GCD.
        $gcd = Integer::gcd($num, $den);

        // Reduce the fraction if necessary.
        if ($gcd > 1) {
            $num = intdiv($num, $gcd);
            $den = intdiv($den, $gcd);
        }

        // Return the simplified fraction, ensuring the denominator is positive.
        return $den < 0 ? [-$num, -$den] : [$num, $den];
    }

    // endregion
}
