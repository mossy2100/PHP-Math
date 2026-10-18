<?php

declare(strict_types=1);

namespace OceanMoon\Math;

use DomainException;
use InvalidArgumentException;
use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Core\Exceptions\FormatException;
use OceanMoon\Core\Floats;
use OceanMoon\Core\Integers;
use OceanMoon\Core\Numbers;
use OceanMoon\Core\Stringify;
use OceanMoon\Core\Traits\Comparison\ApproxComparable;
use OverflowException;
use Override;
use RoundingMode;
use Stringable;

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
 * The reason is that PHP_INT_MIN equals -(PHP_INT_MAX + 1), i.e. it cannot be negated without overflowing.
 * Allowing the numerator or the denominator to equal PHP_INT_MIN therefore complicates negation, reciprocal,
 * subtraction, and simplification methods.
 * So, while it's technically possible, supporting this edge case inflates the code for little gain.
 */
/** @disregard P1128 */
final class Rational implements Stringable
{
    use ApproxComparable;

    #region Properties

    /**
     * The numerator.
     */
    private(set) int $numerator;

    /**
     * The denominator.
     */
    private(set) int $denominator;

    #endregion

    #region Constructor

    /**
     * Constructor.
     *
     * @param int $num The numerator. Defaults to 0.
     * @param int $den The denominator. Defaults to 1.
     * @throws ArithmeticException If the denominator is zero.
     * @throws DomainException If one argument is PHP_INT_MIN and the other is odd, which produces a ratio that cannot
     * be represented as a Rational.
     */
    public function __construct(int $num = 0, int $den = 1)
    {
        // Check for zero denominator.
        if ($den === 0) {
            throw new ArithmeticException('Cannot create Rational with denominator of zero.');
        }

        // If either value is PHP_INT_MIN and the other is odd, then the ratio is unrepresentable.
        if (($num === PHP_INT_MIN && $den % 2 !== 0) || ($den === PHP_INT_MIN && $num % 2 !== 0)) {
            throw new DomainException("Cannot express ratio $num/$den as Rational.");
        }

        // Simplify the ratio to canonical form.
        if ($num === 0) {
            // A zero numerator is always represented as 0/1.
            $den = 1;
        } elseif ($num === $den) {
            // The numerator and denominator are equal to each other.
            $num = 1;
            $den = 1;
        } elseif ($num === -$den) {
            // The numerator is equal to the negative of the denominator.
            $num = -1;
            $den = 1;
        } else {
            // Calculate the GCD.
            $gcd = Integers::gcd($num, $den);

            // Reduce the fraction if necessary.
            if ($gcd > 1) {
                // Neither of these calls to intdiv() will throw an exception because $gcd cannot be 0 or -1.
                $num = intdiv($num, $gcd);
                $den = intdiv($den, $gcd);
            }

            // Ensure the denominator is positive.
            if ($den < 0) {
                $num = -$num;
                $den = -$den;
            }
        }

        // Set the properties.
        $this->numerator = $num;
        $this->denominator = $den;
    }

    #endregion

    #region Factory methods

    /**
     * Create a Rational from a float, approximating it if necessary.
     *
     * If the float is actually a whole number (e.g. 3.0), it's converted directly.
     * Otherwise, it's approximated using continued fractions.
     * This finds the simplest rational that equals the provided number (or is as close as is practical).
     * @see https://en.wikipedia.org/wiki/Simple_continued_fraction
     *
     * The valid range for the absolute value of a Rational is 1/PHP_INT_MAX to PHP_INT_MAX/1.
     * This method will throw an exception if the absolute value of $value is non-zero and outside that range.
     *
     * Float representation limits can cause inexact round-trip conversions for values very close to integers.
     *
     * @param float $value The float to convert.
     * @return self The equivalent (or closest approximating) Rational.
     * @throws DomainException If $value is non-finite or outside the valid range for Rational values.
     */
    public static function fromFloat(float $value): self
    {
        // Check for non-finite values.
        if (!is_finite($value)) {
            throw new DomainException(Stringify::prepEx('Cannot create Rational from non-finite float: ?.', $value));
        }

        // Check if the value equals a valid integer.
        try {
            $i = Floats::toInt($value);
            if ($i > PHP_INT_MIN) {
                return new self($i);
            }
        } catch (DomainException) {
            // Fall through to continued fractions algorithm.
        }

        // Get number info and range limits.
        $absValue = abs($value);
        $sign = Numbers::sign($value, false);
        $min = 1.0 / PHP_INT_MAX;
        $max = (float) PHP_INT_MAX;

        // Check for values outside the valid range for Rational.
        if ($absValue < $min || $absValue > $max) {
            throw new DomainException(
                Stringify::prepEx('Cannot create Rational from float: ?. Outside valid range.', $value)
            );
        }

        // Check for values at the limits of the valid range, which cannot be handled by the continued fractions
        // algorithm.
        if ($absValue === $min) {
            return new self($sign, PHP_INT_MAX);
        } elseif ($absValue === $max) {
            return new self($sign * PHP_INT_MAX, 1);
        }

        // Use the continued fractions algorithm to convert the float to the closest possible Rational.

        // Track the best approximation found so far. Initialize to the nearest integer.
        $hBest = (int) round($absValue);
        $kBest = 1;

        // Initialize convergents.
        $h0 = 1;
        $h1 = 0;
        $k0 = 0;
        $k1 = 1;

        // Get the initial approximation and minimum error.
        $x = $absValue;
        $minErr = abs($hBest - $absValue);

        // Loop until done.
        while (true) {
            // Extract integer part.
            $a = (int) $x;

            // Calculate next convergent.
            $hNew = $a * $h0 + $h1;
            $kNew = $a * $k0 + $k1;

            // If the numerator or the denominator overflows the range for integers, cease the loop and return the best
            // approximation found so far.
            // @phpstan-ignore-next-line
            if (is_float($hNew) || is_float($kNew)) {
                return new self($sign * $hBest, $kBest);
            }

            // Check if we've found an exact representation.
            $err = (float) abs($hNew / $kNew - $absValue);
            if ($err === 0.0) {
                return new self($sign * $hNew, $kNew);
            }

            // Check if this convergent is better than the best so far.
            if ($err < $minErr) {
                $hBest = $hNew;
                $kBest = $kNew;
                $minErr = $err;
            }

            // Update convergents.
            $h1 = $h0;
            $h0 = $hNew;
            $k1 = $k0;
            $k0 = $kNew;

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
     * Convert a string into a Rational.
     *
     * It will accept string values of the following form:
     * - int, e.g. "123", "-456"
     * - float, e.g. "123.456", "-456.789"
     * - fraction, e.g. "1/2", "-3/4"
     *
     * If the string represents a float, it will be converted to the closest Rational if it's within the valid
     * range.
     *
     * The input string is trimmed, including fraction parts. Therefore, the following examples are all allowed:
     * - " 123", "-456 ", etc.
     * - " 123.456", "-456.789 ", etc.
     * - " 1/2", "-3/4 ", " 5 / 6", etc.
     *
     * @param string $str The string to convert.
     * @return self A new Rational.
     * @throws FormatException If the string does not represent a valid Rational.
     * @throws ArithmeticException If the denominator is 0.
     * @throws DomainException If the value represented by the string is outside the valid range.
     */
    public static function fromString(string $str): self
    {
        // Trim whitespace.
        $str = trim($str);

        // Handle empty string
        if ($str === '') {
            throw new FormatException('Cannot convert empty string to Rational.');
        }

        // Check for a string that looks like an integer.
        $n = filter_var($str, FILTER_VALIDATE_INT);
        if (is_int($n)) {
            return new self($n);
        }

        // Check for a string that looks like a float.
        $n = filter_var($str, FILTER_VALIDATE_FLOAT);
        if (is_float($n)) {
            return self::fromFloat($n);
        }

        // Check for a string that looks like a fraction (int/int).
        $parts = explode('/', $str);
        if (count($parts) === 2) {
            $n = filter_var(trim($parts[0]), FILTER_VALIDATE_INT);
            $d = filter_var(trim($parts[1]), FILTER_VALIDATE_INT);
            if (is_int($n) && is_int($d)) {
                return new self($n, $d);
            }
        }

        // Invalid format. Include the string in the message, abbreviated, unless it isn't valid UTF-8, which Stringify
        // can't show.
        throw new FormatException(
            mb_check_encoding($str, 'UTF-8')
                ? Stringify::prepEx('Cannot convert string ? to Rational. Invalid format.', $str)
                : 'Cannot convert string to Rational. Invalid format.'
        );
    }

    #endregion

    #region Conversion methods

    /**
     * Convert the Rational to a float.
     *
     * @return float The equivalent float.
     */
    public function toFloat(): float
    {
        return $this->numerator / $this->denominator;
    }

    /**
     * Convert to a mixed number representation: an integer part and a fractional part.
     *
     * Uses trunc/frac semantics: the integer part truncates toward zero, and the fractional part carries the same sign
     * as the original. In this way, the two can be added to reconstruct the original value.
     *
     * For example:
     *     9/4 → [ 2,  1/4] (i.e.  2 + 1/4    =  9/4).
     *    -9/4 → [-2, -1/4] (i.e. -2 + (-1/4) = -9/4).
     *
     * For proper fractions (|numerator| < denominator), the integer part is 0.
     *
     * @return array{int, self} A tuple of [integer part, fractional remainder].
     */
    public function toMixedNumber(): array
    {
        // The remainder has the same sign as this Rational.
        return [
            $this->trunc(),
            new self($this->numerator % $this->denominator, $this->denominator),
        ];
    }

    /**
     * Convert the Rational to a string. (Stringable implementation.)
     *
     * @return string The string representation of the Rational.
     */
    #[Override] // Stringable
    public function __toString(): string
    {
        return $this->numerator . ($this->denominator === 1 ? '' : '/' . $this->denominator);
    }

    #endregion

    #region Inspection methods

    /**
     * Check if this Rational represents an integer value.
     *
     * @return bool True if the denominator is 1, otherwise false.
     */
    public function isInt(): bool
    {
        return $this->denominator === 1;
    }

    /**
     * Get the sign of this Rational.
     *
     * The numerator is always a plain int, never a signed zero, so $zeroForZero only matters for a zero value:
     * with the default true, zero returns 0; with false, zero returns 1 (there's no -0 equivalent for a
     * Rational, unlike Numbers::sign()'s float case).
     *
     * @param bool $zeroForZero If true (default), return 0 when this Rational is zero. If false, return 1.
     * @return int -1 if negative, 0 if zero (or 1 if zero and $zeroForZero is false), or 1 if positive.
     */
    public function sign(bool $zeroForZero = true): int
    {
        return Numbers::sign($this->numerator, $zeroForZero);
    }

    #endregion

    #region Comparison methods

    /**
     * Compare a Rational with another number.
     *
     * NB: This method works with ±INF.
     * If $other is INF, the value will always be -1.
     * If $other is -INF, the value will always be 1.
     *
     * @param mixed $other The number to compare with.
     * @return int Returns -1 if this < other, 0 if equal, 1 if this > other.
     * @throws InvalidArgumentException If $other is not a Rational, int, or float.
     * @throws DomainException If $other is NAN.
     */
    /** @disregard P1128 */
    #[Override] // Comparable
    public function compare(mixed $other): int
    {
        // Check type.
        if (!$other instanceof self && !Numbers::isNumber($other)) {
            throw new InvalidArgumentException(
                'Cannot compare Rational with ' . get_debug_type($other) . '. Must be Rational, int, or float.'
            );
        }

        // If the parameter is an int, it is preferable to convert it to a equivalent Rational for comparison purposes
        // in order to avoid loss of precision caused by converting a Rational to a float.
        if (is_int($other)) {
            // We can't create a Rational from PHP_INT_MIN, but we know any Rational is greater than PHP_INT_MIN.
            if ($other === PHP_INT_MIN) {
                return 1;
            }

            // Convert the int to a Rational.
            $other = new self($other);
        }

        // Compare with a float exactly, rather than converting this Rational to a float, which can round.
        if (is_float($other)) {
            // Fail on NAN - no meaningful result.
            if (is_nan($other)) {
                throw new DomainException('Cannot compare Rational with NAN.');
            }

            // Every Rational is strictly between -2^63 and 2^63, so a float outside that range decides the result.
            $limit = -(float) PHP_INT_MIN;
            if ($other >= $limit) {
                return -1;
            }
            if ($other <= -$limit) {
                return 1;
            }

            // Fast path: if the numerator and denominator are exactly representable as floats, toFloat() is correctly
            // rounded. Rounding preserves order, so if the result differs from $other, it's on the same side.
            if (abs($this->numerator) <= 2 ** 53 && $this->denominator <= 2 ** 53) {
                $value = $this->toFloat();
                if ($value !== $other) {
                    return $value <=> $other;
                }
            }

            return $this->compareExact($other);
        }

        // $other is a Rational.
        // If the denominators are equal, we only need to compare numerators.
        if ($this->denominator === $other->denominator) {
            return Numbers::sign($this->numerator <=> $other->numerator);
        }

        try {
            // We can avoid a float conversion by cross multiplying and comparing a*d with b*c (for a/b vs c/d).
            $ad = Integers::mul($this->numerator, $other->denominator);
            $bc = Integers::mul($this->denominator, $other->numerator);
            return Numbers::sign($ad <=> $bc);
        } catch (OverflowException) {
            // In case of integer overflow, use the slower exact comparison.
            return $this->compareExact($other);
        }
    }

    /**
     * Check if this Rational approximately equals another one, within specified tolerances.
     *
     * This method uses a combined absolute and relative tolerance approach, matching the algorithm in
     * Floats::approxEqual(). The absolute tolerance is checked first (useful for comparisons near zero), and if that
     * fails, the relative tolerance is checked (which scales with the magnitude of the values).
     *
     * To compare using only absolute difference, set $relTol to 0.0.
     * To compare using only relative difference, set $absTol to 0.0.
     *
     * @param mixed $other The int, float, or Rational to compare with.
     * @param float $relTol The maximum allowed relative difference (default: 1e-9).
     * @param float $absTol The maximum allowed absolute difference (default: PHP_FLOAT_EPSILON).
     * @return bool True if the values are equal within the given tolerances, false otherwise.
     * @throws InvalidArgumentException If the value cannot be converted to a Rational.
     * @throws DomainException If $other is NAN. There's no meaningful answer for NAN, unlike ±INF, which a Rational
     * is simply never (approximately) equal to.
     * @see Floats::approxEqual() For the tolerance algorithm details.
     */
    /** @disregard P1128 */
    #[Override] // ApproxEquatable
    public function approxEqual(
        mixed $other,
        float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
        float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
    ): bool {
        // Check type.
        if (!$other instanceof self && !Numbers::isNumber($other)) {
            throw new InvalidArgumentException(
                'Cannot compare Rational with ' . get_debug_type($other) . '. Must be Rational, int, or float.'
            );
        }

        // Fail on NAN - no meaningful result. ±INF falls through to Floats::approxEqual() below, which already
        // handles it correctly (a finite Rational is never exactly equal to infinity).
        if (is_float($other) && is_nan($other)) {
            throw new DomainException('Cannot compare Rational with NAN.');
        }

        // Convert Rational to float.
        if ($other instanceof self) {
            $other = $other->toFloat();
        }

        // Compare as floats.
        return Floats::approxEqual($this->toFloat(), $other, $relTol, $absTol);
    }

    #endregion

    #region Unary arithmetic methods

    /**
     * Calculate the absolute value of this Rational.
     *
     * @return self A new Rational representing the absolute value.
     */
    public function abs(): self
    {
        return new self(abs($this->numerator), $this->denominator);
    }

    /**
     * Calculate the negative of this Rational.
     *
     * @return self A new Rational representing the negative.
     */
    public function neg(): self
    {
        return new self(-$this->numerator, $this->denominator);
    }

    /**
     * Calculate the reciprocal of this Rational.
     *
     * @return self A new Rational representing the reciprocal.
     * @throws ArithmeticException If the value is zero.
     */
    public function inv(): self
    {
        // Guard.
        if ($this->numerator === 0) {
            throw new ArithmeticException('Cannot divide by zero.');
        }

        // Preserve sign: if num is negative, swap and negate.
        return $this->numerator > 0
            ? new self($this->denominator, $this->numerator)
            : new self(-$this->denominator, -$this->numerator);
    }

    #endregion

    #region Binary arithmetic methods

    /**
     * Add a value to this Rational.
     *
     * @param self|int $other The value to add.
     * @return self A new Rational representing the sum.
     * @throws OverflowException If integer overflow occurs.
     * @throws DomainException If the result cannot be expressed as a Rational.
     */
    public function add(self|int $other): self
    {
        [$a, $b, $c, $d] = $this->getOperandComponents($other);
        return self::calcWithOverflowMessage(
            'add the given Rational numbers',
            static fn () => self::calcAddSub($a, $b, $c, $d, false)
        );
    }

    /**
     * Subtract a value from this Rational.
     *
     * @param self|int $other The value to subtract.
     * @return self A new Rational representing the difference.
     * @throws OverflowException If integer overflow occurs.
     * @throws DomainException If the result cannot be expressed as a Rational.
     */
    public function sub(self|int $other): self
    {
        [$a, $b, $c, $d] = $this->getOperandComponents($other);
        return self::calcWithOverflowMessage(
            'subtract the given Rational numbers',
            static fn () => self::calcAddSub($a, $b, $c, $d, true)
        );
    }

    /**
     * Multiply this Rational by a value.
     *
     * @param self|int $other The value to multiply by.
     * @return self A new Rational representing the product.
     * @throws OverflowException If integer overflow occurs.
     * @throws DomainException If the result cannot be expressed as a Rational.
     */
    public function mul(self|int $other): self
    {
        [$a, $b, $c, $d] = $this->getOperandComponents($other);

        // To avert integer overflow, cross-cancel before multiplying.
        // Cancel gcd(a,d) from a and d.
        $gcd1 = Integers::gcd($a, $d);
        if ($gcd1 !== 1) {
            $a = intdiv($a, $gcd1);
            $d = intdiv($d, $gcd1);
        }
        // Cancel gcd(b,c) from b and c.
        $gcd2 = Integers::gcd($b, $c);
        if ($gcd2 !== 1) {
            $b = intdiv($b, $gcd2);
            $c = intdiv($c, $gcd2);
        }

        // Multiply reduced terms: (a/b) * (c/d) = ac/bd
        return self::calcWithOverflowMessage(
            'multiply the given Rational numbers',
            static fn () => new self(Integers::mul($a, $c), Integers::mul($b, $d))
        );
    }

    /**
     * Divide this Rational by a value.
     *
     * @param self|int $other The value to divide by.
     * @return self A new Rational representing the quotient.
     * @throws OverflowException If integer overflow occurs.
     * @throws DomainException If the result cannot be expressed as a Rational.
     * @throws ArithmeticException If dividing by zero.
     */
    public function div(self|int $other): self
    {
        // Check for divide by 0.
        if ((is_int($other) && $other === 0) || ($other instanceof self && $other->numerator === 0)) {
            throw new ArithmeticException('Cannot divide by zero.');
        }

        // Check for 0 divide by something.
        if ($this->numerator === 0) {
            // Return 0.
            return new self();
        }

        [$a, $b, $c, $d] = $this->getOperandComponents($other);

        // To avert integer overflow, cross-cancel before multiplying.
        // Cancel gcd(a,c) from a and c.
        $gcd1 = Integers::gcd($a, $c);
        if ($gcd1 !== 1) {
            $a = intdiv($a, $gcd1);
            $c = intdiv($c, $gcd1);
        }
        // Cancel gcd(b,d) from b and d.
        $gcd2 = Integers::gcd($b, $d);
        if ($gcd2 !== 1) {
            $b = intdiv($b, $gcd2);
            $d = intdiv($d, $gcd2);
        }

        // Multiply reduced terms: (a/b) / (c/d) = ad/bc
        return self::calcWithOverflowMessage(
            'divide the given Rational numbers',
            static fn () => new self(Integers::mul($a, $d), Integers::mul($b, $c))
        );
    }

    /**
     * Find the remainder of dividing this Rational by a value.
     *
     * The result has the sign of the dividend, matching PHP's % operator and fmod().
     *
     * @param self|int $other The divisor.
     * @return self The remainder.
     * @throws ArithmeticException If dividing by zero.
     * @throws OverflowException If integer overflow occurs.
     * @throws DomainException If the result cannot be expressed as a Rational.
     */
    public function mod(self|int $other): self
    {
        // Check for divide by 0.
        if ($other === 0 || ($other instanceof self && $other->numerator === 0)) {
            throw new ArithmeticException('Cannot divide by zero.');
        }

        // If |this| < |divisor|, the remainder is this Rational. abs(PHP_INT_MIN) is a float, which compare() handles
        // exactly.
        if ($this->abs()->compare(is_int($other) ? abs($other) : $other->abs()) < 0) {
            return $this;
        }

        [$a, $b, $c, $d] = $this->getOperandComponents($other);

        // Over the common denominator lcm(b, d), this Rational is h/lcm and the divisor is k/lcm, where
        // h = a·(d/g) and k = c·(b/g), so the remainder is (h % k)/lcm. h can overflow even when the remainder is
        // small, so reduce a first: h % k = ((a % k)·(d/g)) % k.
        $g = Integers::gcd($b, $d);
        $bOverG = intdiv($b, $g);
        return self::calcWithOverflowMessage(
            'find the remainder of the given Rational numbers',
            static function () use ($a, $c, $d, $g, $bOverG): self {
                $k = Integers::mul($c, $bOverG);
                $lcm = Integers::mul($bOverG, $d);
                return new self(Integers::mul($a % $k, intdiv($d, $g)) % $k, $lcm);
            }
        );
    }

    #endregion

    #region Power methods

    /**
     * Raise this Rational to an integer or Rational power.
     *
     * A Rational exponent p/q (in lowest terms) means the q-th root, raised to the power p. The result must itself be
     * rational, which it is only when this Rational's numerator and denominator are both perfect q-th powers. That's
     * determined with integer arithmetic only. For example, (4/9) ** (1/2) is 2/3, but (2/9) ** (1/2) is irrational, so
     * it throws.
     *
     * A negative base has a real result only for an odd root (odd q). For example, (-8/27) ** (1/3) is -2/3.
     *
     * For example:
     * ```php
     * new Rational(2, 3)->pow(2);                    // 4/9
     * new Rational(4, 9)->pow(new Rational(1, 2));   // 2/3
     * new Rational(8, 27)->pow(new Rational(-2, 3)); // 9/4
     * ```
     *
     * @param int|self $exp The exponent.
     * @return self A new Rational representing the result.
     * @throws ArithmeticException If raising zero to a negative power.
     * @throws OverflowException If integer overflow occurs.
     * @throws DomainException If the result cannot be expressed as a Rational.
     */
    public function pow(int|self $exp): self
    {
        return self::calcWithOverflowMessage(
            'raise the given Rational to the given power',
            fn () => $this->calcPow($exp)
        );
    }

    /**
     * Square this Rational.
     *
     * Equivalent to pow(2), but more readable as a standalone call.
     *
     * @return self A new Rational representing the square of this number.
     */
    public function sqr(): self
    {
        return self::calcWithOverflowMessage('square the given Rational', fn () => $this->mul($this));
    }

    #endregion

    #region Rounding methods

    /**
     * Find the integer closest to the Rational, using the specified rounding mode.
     *
     * All arithmetic is performed exactly on the numerator and denominator -- the Rational is never converted to a
     * float, so there's no precision loss near tie boundaries or for a numerator/denominator beyond float's 53-bit
     * mantissa, both of which a float-based implementation (converting to float, rounding, then converting back)
     * would be vulnerable to.
     *
     * @param RoundingMode $mode The rounding mode to use. Defaults to HalfAwayFromZero, matching the default mode
     * used by PHP's own round() function.
     * @return int The rounded integer.
     */
    public function round(RoundingMode $mode = RoundingMode::HalfAwayFromZero): int
    {
        if ($this->denominator === 1) {
            return $this->numerator;
        }

        // Since the Rational is always in lowest terms and the denominator isn't 1 (checked above), the
        // denominator cannot evenly divide the numerator, so the remainder is guaranteed to be non-zero.
        $q = intdiv($this->numerator, $this->denominator);
        $r = $this->numerator % $this->denominator;
        $absR = abs($r);
        $away = $this->numerator > 0 ? $q + 1 : $q - 1;

        return match ($mode) {
            // Always truncate toward zero, ignoring the remainder entirely.
            RoundingMode::TowardsZero => $q,

            // Always round away from zero, since there's always a non-zero remainder here.
            RoundingMode::AwayFromZero => $away,

            // Round down (toward negative infinity). Truncation already does this for a positive value; a
            // negative value needs its magnitude increased by one.
            RoundingMode::NegativeInfinity => $this->numerator > 0 ? $q : $away,

            // Round up (toward positive infinity). Mirror image of NegativeInfinity.
            RoundingMode::PositiveInfinity => $this->numerator > 0 ? $away : $q,

            // Round to the nearest integer; an exact tie (remainder exactly half the denominator) rounds away
            // from zero. Comparing $absR against ($denominator - $absR) is equivalent to comparing 2 * $absR
            // against $denominator, but avoids the doubling ever overflowing for a remainder near PHP_INT_MAX.
            RoundingMode::HalfAwayFromZero => $absR >= $this->denominator - $absR ? $away : $q,

            // Round to the nearest integer; an exact tie rounds toward zero.
            RoundingMode::HalfTowardsZero => $absR > $this->denominator - $absR ? $away : $q,

            // Round to the nearest integer; an exact tie rounds to whichever of $q/$away is even ("banker's
            // rounding"). $q and $away always differ by exactly 1, so exactly one of them is even.
            RoundingMode::HalfEven => match (true) {
                $absR > $this->denominator - $absR => $away,
                $absR < $this->denominator - $absR => $q,
                default => $q % 2 === 0 ? $q : $away,
            },

            // Round to the nearest integer; an exact tie rounds to whichever of $q/$away is odd.
            RoundingMode::HalfOdd => match (true) {
                $absR > $this->denominator - $absR => $away,
                $absR < $this->denominator - $absR => $q,
                default => $q % 2 !== 0 ? $q : $away,
            },
        };
    }

    /**
     * Find the closest integer less than or equal to the Rational.
     *
     * @return int The floored value.
     */
    public function floor(): int
    {
        if ($this->denominator === 1) {
            return $this->numerator;
        }

        // Truncating rounds a negative fraction up (toward zero), so subtract 1 to floor it (toward negative
        // infinity). For a positive fraction, truncating already rounds down, which is the floor.
        $q = $this->trunc();
        return $this->numerator < 0 ? $q - 1 : $q;
    }

    /**
     * Find the closest integer greater than or equal to the Rational.
     *
     * @return int The ceiling value.
     */
    public function ceil(): int
    {
        if ($this->denominator === 1) {
            return $this->numerator;
        }

        // Truncating rounds a positive fraction down (toward zero), so add 1 to ceil it (toward positive
        // infinity). For a negative fraction, truncating already rounds up, which is the ceiling.
        $q = $this->trunc();
        return $this->numerator > 0 ? $q + 1 : $q;
    }

    /**
     * Find the integer part of the Rational, truncating toward zero.
     *
     * Equivalent to round(RoundingMode::TowardsZero), and to the integer part of toMixedNumber().
     *
     * @return int The truncated value.
     */
    public function trunc(): int
    {
        // The denominator is always positive, so intdiv() can't overflow.
        return intdiv($this->numerator, $this->denominator);
    }

    #endregion

    #region Helper methods

    /**
     * Helper function for arithmetic methods, when it's useful to get the Rational components as individual integers.
     *
     * @param int|self $other The other value in the operation.
     * @return array{int, int, int, int} The components.
     */
    private function getOperandComponents(int|self $other): array
    {
        $a = $this->numerator;
        $b = $this->denominator;

        if (is_int($other)) {
            $c = $other;
            $d = 1;
        } else {
            $c = $other->numerator;
            $d = $other->denominator;
        }

        return [$a, $b, $c, $d];
    }

    /**
     * Calculate the result for pow().
     *
     * @param int|self $exp The exponent.
     * @return self The result.
     * @throws ArithmeticException If raising zero to a negative power.
     * @throws OverflowException If integer overflow occurs.
     * @throws DomainException If the result cannot be expressed as a Rational.
     */
    private function calcPow(int|self $exp): self
    {
        // Handle a Rational exponent.
        if ($exp instanceof self) {
            $p = $exp->numerator;
            $q = $exp->denominator;

            // A whole-number exponent is handled the same as an int.
            if ($q === 1) {
                return $this->calcPow($p);
            }

            // The result is the q-th root, raised to the power p. It's rational only if the numerator and denominator
            // both have exact q-th roots.
            $rootNum = self::exactRoot($this->numerator, $q);
            $rootDen = self::exactRoot($this->denominator, $q);
            if ($rootNum === null || $rootDen === null) {
                $notReal = $this->numerator < 0 && $q % 2 === 0;
                $reason = $notReal ? 'The result is not real.' : 'The result is irrational.';
                throw new DomainException("Cannot raise $this to power $exp. $reason");
            }
            return new self($rootNum, $rootDen)->calcPow($p);
        }

        // $exp is an integer.
        // Any number to the power of 0 is 1, including 0.
        // 0^0 can be considered undefined, but many programming languages (including PHP) return 1.
        if ($exp === 0) {
            return new self(1);
        }

        // Handle 0 base.
        if ($this->numerator === 0) {
            // 0 to the power of a negative exponent is invalid (effectively division by zero).
            if ($exp < 0) {
                throw new ArithmeticException('Cannot raise zero to negative power.');
            }

            // 0 to the power of a positive exponent is 0.
            return new self(0);
        }

        // Handle exponent = 1. Any number to power 1 is itself.
        if ($exp === 1) {
            return clone $this;
        }

        // Handle exponent = 2.
        if ($exp === 2) {
            return $this->mul($this);
        }

        // Handle exponent = -1. Delegate to inv().
        if ($exp === -1) {
            return $this->inv();
        }

        // Handle exponent = PHP_INT_MIN.
        if ($exp === PHP_INT_MIN) {
            return $this->calcPow(PHP_INT_MAX)->mul($this)->inv();
        }

        // Handle negative exponents by taking reciprocal.
        if ($exp < 0) {
            return $this->inv()->calcPow(-$exp);
        }

        // General solution. Calculate the new numerator and denominator with overflow checks.
        return new self(
            Integers::pow($this->numerator, $exp),
            Integers::pow($this->denominator, $exp)
        );
    }

    /**
     * Run a calculation, replacing the message of an OverflowException from any step with one that names the
     * operation the caller ran.
     *
     * @param string $operation The operation, for the exception message, e.g. 'add the given Rational numbers'.
     * @param callable(): self $calc The calculation.
     * @param-immediately-invoked-callable $calc
     * @return self The result.
     * @throws OverflowException If the result, or an intermediate value, is too large to represent as an int.
     */
    private static function calcWithOverflowMessage(string $operation, callable $calc): self
    {
        try {
            return $calc();
        } catch (OverflowException $e) {
            throw new OverflowException("Cannot $operation. The result would overflow.", 0, $e);
        }
    }

    /**
     * Calculate (a/b) + (c/d) or (a/b) - (c/d), for add() and sub().
     *
     * To keep the intermediate values small, the integer parts are added separately, and the fractions are added with
     * Knuth's method (The Art of Computer Programming, Vol. 2, 4.5.1), which divides out the gcd of the denominators
     * before multiplying. For example, 1/6000000000 + 1/4000000000 doesn't overflow, although 6000000000 × 4000000000
     * would.
     *
     * @param int $a The numerator of the first value.
     * @param int $b The denominator of the first value. Must be positive.
     * @param int $c The numerator of the second value.
     * @param int $d The denominator of the second value. Must be positive.
     * @param bool $subtract True to subtract, false to add.
     * @return self The result.
     * @throws OverflowException If integer overflow occurs.
     * @throws DomainException If the result cannot be expressed as a Rational.
     */
    private static function calcAddSub(int $a, int $b, int $c, int $d, bool $subtract): self
    {
        // Split each value into an integer part and a fraction, e.g. 7/2 = 3 + 1/2, and add them separately.
        $intPart = $subtract
            ? Integers::sub(intdiv($a, $b), intdiv($c, $d))
            : Integers::add(intdiv($a, $b), intdiv($c, $d));
        $a %= $b;
        $c %= $d;

        // Let g = gcd(b, d). Then (a/b) ± (c/d) = t / ((b/g)·d), where t = a·(d/g) ± c·(b/g).
        $g = Integers::gcd($b, $d);
        $bOverG = intdiv($b, $g);
        $ad = Integers::mul($a, intdiv($d, $g));
        $cb = Integers::mul($c, $bOverG);
        $t = $subtract ? Integers::sub($ad, $cb) : Integers::add($ad, $cb);

        // t has no common factor with b/g or d/g, so the only factor to cancel is gcd(t, g). The result is reduced.
        $g2 = Integers::gcd($t, $g);
        $num = intdiv($t, $g2);
        $den = Integers::mul($bOverG, intdiv($d, $g2));

        // Add the integer part.
        return new self(Integers::add(Integers::mul($intPart, $den), $num), $den);
    }

    /**
     * Compare this Rational with another Rational or a float exactly, without overflow.
     *
     * Compares the signs, then the whole number parts of the magnitudes, then their fractions with compareFractions().
     *
     * @param self|float $other The value to compare with. A float must be finite and strictly between -2^63 and 2^63.
     * @return int Returns -1 if this < other, 0 if equal, 1 if this > other.
     */
    private function compareExact(self|float $other): int
    {
        // Compare the signs.
        $sign = $this->numerator <=> 0;
        $otherSign = $other instanceof self ? $other->numerator <=> 0 : $other <=> 0.0;
        if ($sign !== $otherSign || $sign === 0) {
            return $sign <=> $otherSign;
        }

        // The signs are the same, so compare the magnitudes, and flip the result if they're negative. A numerator is
        // never PHP_INT_MIN, as it's in lowest terms, so abs() can't overflow.
        [$whole, $frac] = $this->toMixedNumber();
        if ($other instanceof self) {
            [$otherWhole, $otherFrac] = $other->toMixedNumber();
            $otherWhole = abs($otherWhole);
            $otherRem = abs($otherFrac->numerator);
            $otherDen = $otherFrac->denominator;
        } else {
            $abs = abs($other);
            $otherWhole = (int) floor($abs);
            // By Sterbenz's lemma, this subtraction is exact, as floor($abs) is either 0 or at least $abs / 2.
            $otherRem = $abs - floor($abs);
            $otherDen = 1;
        }

        $result = (abs($whole) <=> $otherWhole)
            ?: self::compareFractions(abs($frac->numerator), $frac->denominator, $otherRem, $otherDen);
        return $sign * $result;
    }

    /**
     * Compare two fractions in the range [0, 1) exactly, one binary digit at a time.
     *
     * The first fraction is rem/den. The second is otherRem/otherDen, or, if otherRem is a float, otherRem itself.
     *
     * This always ends. Two different fractions with denominators below 2^63 differ within 127 binary digits, and a
     * float fraction has at most 1074.
     *
     * @param int $rem The numerator of the first fraction.
     * @param int $den The denominator of the first fraction.
     * @param int|float $otherRem The numerator of the second fraction, or the second fraction as a float.
     * @param int $otherDen The denominator of the second fraction. Ignored if otherRem is a float.
     * @return int Returns -1 if the first fraction is less than the second, 0 if equal, 1 if greater.
     */
    private static function compareFractions(int $rem, int $den, int|float $otherRem, int $otherDen): int
    {
        while (true) {
            // If either fraction has no more digits, the one with digits left is greater.
            if ($rem === 0 || $otherRem == 0) {
                return ($rem > 0) <=> ($otherRem > 0);
            }

            // Get the next digit of each.
            $digit = self::nextBinaryDigit($rem, $den);
            if (is_float($otherRem)) {
                // Doubling a float is exact.
                $otherRem *= 2;
                $otherDigit = $otherRem >= 1 ? 1 : 0;
                $otherRem -= $otherDigit;
            } else {
                $otherDigit = self::nextBinaryDigit($otherRem, $otherDen);
            }

            if ($digit !== $otherDigit) {
                return $digit <=> $otherDigit;
            }
        }
    }

    /**
     * Get the next binary digit of the fraction rem/den, which is less than 1, and update rem to the rest of it.
     *
     * @param int $rem The numerator, updated by reference. Must be non-negative and less than den.
     * @param int $den The denominator.
     * @return int The next binary digit, 0 or 1.
     */
    private static function nextBinaryDigit(int &$rem, int $den): int
    {
        // Double the fraction and take its whole number part, without calculating 2 * rem, which could overflow.
        if ($rem >= $den - $rem) {
            $rem -= $den - $rem;
            return 1;
        }
        $rem += $rem;
        return 0;
    }

    /**
     * Find the exact integer q-th root of n, if there is one.
     *
     * Returns the integer k such that k^q == n, or null if there's no such integer (including an even root of a
     * negative number, which isn't real). Uses integer arithmetic only.
     *
     * @param int $n The number to find the root of.
     * @param int $q The degree of the root. Must be positive.
     * @return ?int The exact root, or null if there isn't one.
     */
    private static function exactRoot(int $n, int $q): ?int
    {
        // Every integer is its own first root, and 0 and 1 are their own roots of any degree.
        if ($q === 1 || $n === 0 || $n === 1) {
            return $n;
        }

        // Handle a negative number.
        if ($n < 0) {
            // An even root of a negative number isn't real.
            if ($q % 2 === 0) {
                return null;
            }

            // An odd root of a negative number is the negative of the root of its magnitude. -n can't overflow, because
            // n is a Rational's numerator, which is never PHP_INT_MIN.
            $root = self::exactRoot(-$n, $q);
            return $root === null ? null : -$root;
        }

        // From here, n >= 2. Since 2^63 > PHP_INT_MAX, no int >= 2 has an exact root of degree 63 or more.
        if ($q >= 63) {
            return null;
        }

        // Binary search for the root. Since q >= 2, the root can't exceed the square root of PHP_INT_MAX
        // (3037000499.97...).
        $low = 1;
        $high = min($n, 3037000499);
        while ($low <= $high) {
            $mid = intdiv($low + $high, 2);
            $cmp = self::comparePower($mid, $q, $n);
            if ($cmp === 0) {
                return $mid;
            }
            if ($cmp < 0) {
                $low = $mid + 1;
            } else {
                $high = $mid - 1;
            }
        }

        return null;
    }

    /**
     * Compare k^q with n, without overflowing.
     *
     * @param int $k The base. Must be positive.
     * @param int $q The exponent. Must be positive.
     * @param int $n The number to compare with. Must be positive.
     * @return int -1, 0, or 1 if k^q is less than, equal to, or greater than n.
     */
    private static function comparePower(int $k, int $q, int $n): int
    {
        $power = 1;
        for ($i = 0; $i < $q; $i++) {
            // If multiplying by k again would exceed n, k^q > n. Checking against intdiv() first means the
            // multiplication below never overflows.
            if ($power > intdiv($n, $k)) {
                return 1;
            }
            $power *= $k;
        }
        return $power <=> $n;
    }

    #endregion
}
