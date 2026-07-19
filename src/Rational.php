<?php

declare(strict_types=1);

namespace OceanMoon\Math;

use DomainException;
use InvalidArgumentException;
use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Core\Exceptions\FormatException;
use OceanMoon\Core\Floats;
use OceanMoon\Core\Integers;
use OceanMoon\Core\Traits\Comparison\ApproxComparable;
use OverflowException;
use Override;
use RoundingMode;
use Stringable;

use function OceanMoon\Core\Globals\ex;
use function OceanMoon\Core\Globals\is_number;
use function OceanMoon\Core\Globals\sign;

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
 * The reason is that PHP_INT_MIN equals -(PHP_INT_MAX + 1), .e. it cannot be negated without overflowing.
 * Allowing the numerator or the denominator to equal PHP_INT_MIN therefore complicates negation, reciprocal,
 * subtraction, and simplification methods.
 * So, while it's technically possible, supporting this edge case inflates the code for little gain.
 */
/** @disregard P1128 */
final class Rational implements Stringable
{
    use ApproxComparable;

    #region Properties

    #region Public properties (readonly)

    /**
     * The numerator.
     */
    private(set) int $numerator;

    /**
     * The denominator.
     */
    private(set) int $denominator;

    #endregion

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
            throw new DomainException('Cannot create Rational from non-finite float: ' . ex($value) . '.');
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
        $sign = sign($value, false);
        $min = 1.0 / PHP_INT_MAX;
        $max = (float) PHP_INT_MAX;

        // Check for values outside the valid range for Rational.
        if ($absValue < $min || $absValue > $max) {
            throw new DomainException(
                'Cannot create Rational from float: ' . ex($value) . '. Outside valid range.'
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

        // Invalid format.
        throw new FormatException('Cannot convert string to Rational. Invalid format.');
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
     *     9/4 → [ 2,  1/4] (.e.  2 + 1/4    =  9/4).
     *    -9/4 → [-2, -1/4] (.e. -2 + (-1/4) = -9/4).
     *
     * For proper fractions (|numerator| < denominator), the integer part is 0.
     *
     * @return array{int, self} A tuple of [integer part, fractional remainder].
     */
    public function toMixedNumber(): array
    {
        // If the numerator is 0, the integer part is 0 and the remainder is 0/1.
        if ($this->numerator === 0) {
            return [0, new self(0)];
        }

        // If the denominator is 1, the integer part is the numerator and the remainder is 0.
        if ($this->denominator === 1) {
            return [$this->numerator, new self(0)];
        }

        // For proper fractions, the integer part is 0 and the remainder is the original fraction.
        if (abs($this->numerator) < $this->denominator) {
            return [0, $this];
        }

        // Calculate the integer part and the remainder. The remainder will have the same sign as
        // the original fraction.
        $int = intdiv($this->numerator, $this->denominator);
        $rem = $this->numerator % $this->denominator;
        return [$int, new self($rem, $this->denominator)];
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
        if (!$other instanceof self && !is_number($other)) {
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

        // If $other is a float, it's quicker to compare $this and $other as floats than it would be to convert the
        // value to a Rational via fromFloat().
        if (is_float($other)) {
            // Fail on NAN - no meaningful result.
            if (is_nan($other)) {
                throw new DomainException('Cannot compare Rational with NAN.');
            }

            // Use the spaceship operator to compare. Note, the spaceship operator only guarantees sign, not specific
            // values, so we call sign to normalize the result to -1, 0, or 1 for predictable behavior.
            return sign($this->toFloat() <=> $other);
        }

        // $other is a Rational.
        // If the denominators are equal, we only need to compare numerators.
        if ($this->denominator === $other->denominator) {
            return sign($this->numerator <=> $other->numerator);
        }

        try {
            // We can avoid a float conversin by cross multiplying and comparing a*d with b*c (for a/b vs c/d).
            $ad = Integers::mul($this->numerator, $other->denominator);
            $bc = Integers::mul($this->denominator, $other->numerator);
            return sign($ad <=> $bc);
        } catch (OverflowException) {
            // In case of integer overflow, compare equivalent floating point values.
            // NB: This could produce a result of 0 (equal) if two different rationals convert to the same float, which
            // is possible for values with a magnitude greater than or equal to 2^53 (64-bit platforms only). But that
            // should be ok.
            return sign($this->toFloat() <=> $other->toFloat());
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
        if (!$other instanceof self && !is_number($other)) {
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

        // (a/b) + (c/d) = (ad + bc) / (bd)
        $ad = Integers::mul($a, $d);
        $bc = Integers::mul($b, $c);
        $h = Integers::add($ad, $bc);
        $k = Integers::mul($b, $d);
        return new self($h, $k);
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

        // (a/b) - (c/d) = (ad - bc) / (bd)
        $ad = Integers::mul($a, $d);
        $bc = Integers::mul($b, $c);
        $h = Integers::sub($ad, $bc);
        $k = Integers::mul($b, $d);
        return new self($h, $k);
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
        $h = Integers::mul($a, $c);
        $k = Integers::mul($b, $d);
        return new self($h, $k);
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
        // Check for divide by 0 .
        if ((is_int($other) && $other === 0) || ($other instanceof self && $other->numerator === 0)) {
            throw new ArithmeticException('Cannot divide by zero.');
        }

        // Check for 0 divide by something.
        if ($this->numerator === 0) {
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

        // Multiply reduced terms: (a/b) / (c/d) = (a/b) * (d/c) = ad/bc
        $h = Integers::mul($a, $d);
        $k = Integers::mul($b, $c);
        return new self($h, $k);
    }

    #endregion

    #region Power methods

    /**
     * Raise this Rational to an integer power.
     *
     * @param int $exponent The exponent.
     * @return self A new Rational representing the result of the exponentiation.
     * @throws ArithmeticException If raising zero to a negative power.
     * @throws OverflowException If integer overflow occurs.
     * @throws DomainException If the result cannot be expressed as a Rational.
     */
    public function pow(int $exponent): self
    {
        // Any number to the power of 0 is 1, including 0.
        // 0^0 can be considered undefined, but many programming languages (including PHP) return 1.
        if ($exponent === 0) {
            return new self(1);
        }

        // Handle 0 base.
        if ($this->numerator === 0) {
            // 0 to the power of a negative exponent is invalid (effectively division by zero).
            if ($exponent < 0) {
                throw new ArithmeticException('Cannot raise zero to negative power.');
            }

            // 0 to the power of a positive exponent is 0.
            return new self(0);
        }

        // Handle exponent = 1. Any number to power 1 is itself.
        if ($exponent === 1) {
            return clone $this;
        }

        // Handle exponent = 2. Delegate to sqr().
        if ($exponent === 2) {
            return $this->sqr();
        }

        // Handle exponent = -1. Delegate to inv().
        if ($exponent === -1) {
            return $this->inv();
        }

        // Handle exponent = PHP_INT_MIN.
        if ($exponent === PHP_INT_MIN) {
            return $this->pow(PHP_INT_MAX)->mul($this)->inv();
        }

        // Handle negative exponents by taking reciprocal.
        if ($exponent < 0) {
            return $this->inv()->pow(-$exponent);
        }

        // General solution. Calculate the new numerator and denominator with overflow checks.
        return new self(
            Integers::pow($this->numerator, $exponent),
            Integers::pow($this->denominator, $exponent)
        );
    }

    /**
     * Square this Rational.
     *
     * Equivalent to pow(2), but more efficient and readable.
     *
     * @return self A new Rational representing the square of this number.
     */
    public function sqr(): self
    {
        return $this->mul($this);
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

        // PHP's intdiv() truncates toward zero, so for negative fractions the quotient is already
        // rounded up (toward zero). We need to subtract 1 to floor it (toward negative infinity).
        // For positive fractions, intdiv() already truncates down, which is the floor.
        $q = intdiv($this->numerator, $this->denominator);
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

        // PHP's intdiv() truncates toward zero, so for positive fractions the quotient is already
        // rounded down (toward zero). We need to add 1 to ceil it (toward positive infinity).
        // For negative fractions, intdiv() already truncates up, which is the ceiling.
        $q = intdiv($this->numerator, $this->denominator);
        return $this->numerator > 0 ? $q + 1 : $q;
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

    #endregion
}
