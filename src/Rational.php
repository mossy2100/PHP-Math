<?php

declare(strict_types=1);

namespace OceanMoon\Math;

use DomainException;
use Exception;
use InvalidArgumentException;
use JsonSerializable;
use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Core\Exceptions\FormatException;
use OceanMoon\Core\Floats;
use OceanMoon\Core\Integers;
use OceanMoon\Core\Traits\Comparison\ApproxComparable;
use OverflowException;
use Override;
use Stringable;
use UnderflowException;

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
final class Rational implements Stringable, JsonSerializable
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
     * be represented as a Rational and cannot be simplified.
     */
    public function __construct(int $num = 0, int $den = 1)
    {
        // Check for zero denominator.
        if ($den === 0) {
            throw new ArithmeticException('Cannot create a Rational with a denominator of zero.');
        }

        try {
            // Simplify the ratio to canonical form.
            [$num, $den] = self::simplify($num, $den);
        } catch (DomainException) {
            if ($den === 1) {
                throw new DomainException("The value $num is outside the valid range for a Rational.");
            }
            throw new DomainException("Cannot express the ratio $num/$den as a Rational.");
        }

        // Store the simplified values.
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
     *
     * If an exact match is not found, the method will return the closest approximation with a denominator less than
     * or equal to PHP_INT_MAX. This is likely to be a more useful result than an exception and limits the time spent
     * in the method.
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
            throw new DomainException('Cannot convert float to Rational. Value must be a finite number.');
        }

        // Check if the value equals a valid integer.
        $i = Floats::tryConvertToInt($value);
        if (is_int($i) && $i > PHP_INT_MIN) {
            return new self($i);
        }

        // Get number info and range limits.
        $absValue = abs($value);
        $sign = sign($value, false);
        $min = 1.0 / PHP_INT_MAX;
        $max = (float) PHP_INT_MAX;

        // Check for values outside the valid range for Rational.
        if ($absValue < $min || $absValue > $max) {
            throw new DomainException("Cannot convert float to Rational. Value $value is outside the valid range.");
        }

        // Check for limits of range, which cannot be handled by the continued fraction algorithm.
        if ($absValue === $min) {
            return new self($sign, PHP_INT_MAX);
        } elseif ($absValue === $max) {
            return new self($sign * PHP_INT_MAX, 1);
        }

        // Use the continued fraction algorithm to convert the float to the closest possible Rational.

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
            throw new FormatException('Cannot convert string to Rational. String must not be empty.');
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
            throw new ArithmeticException('Cannot take reciprocal of zero.');
        }

        // Preserve sign: if num is negative, swap and negate.
        return $this->numerator > 0
            ? new self($this->denominator, $this->numerator)
            : new self(-$this->denominator, -$this->numerator);
    }

    #endregion

    #region Binary arithmetic methods

    /**
     * Add another value to this Rational.
     *
     * @param self|int|float $other The value to add.
     * @return self A new Rational representing the sum.
     * @throws DomainException If $other is a number outside the valid range for Rational.
     * @throws OverflowException If the result overflows an integer.
     */
    public function add(self|int|float $other): self
    {
        // Get other value as a Rational.
        $other = self::toRational($other);

        // (a/b) + (c/d) = (ad + bc) / (bd)
        $f = Integers::mul($this->numerator, $other->denominator);
        $g = Integers::mul($this->denominator, $other->numerator);
        $h = Integers::add($f, $g);
        $k = Integers::mul($this->denominator, $other->denominator);

        return new self($h, $k);
    }

    /**
     * Subtract another value from this Rational.
     *
     * @param self|int|float $other The value to subtract.
     * @return self A new Rational representing the difference.
     * @throws DomainException If $other is a number outside the valid range for Rational.
     * @throws OverflowException If the result overflows an integer.
     */
    public function sub(self|int|float $other): self
    {
        // Get other value as a Rational.
        $other = self::toRational($other);

        return $this->add($other->neg());
    }

    /**
     * Multiply this Rational by another value.
     *
     * @param self|int|float $other The value to multiply by.
     * @return self A new Rational representing the product.
     * @throws DomainException If $other is a number outside the valid range for Rational.
     * @throws OverflowException If the result overflows an integer.
     */
    public function mul(self|int|float $other): self
    {
        // Get other value as a Rational.
        $other = self::toRational($other);

        // Cross-cancel before multiplying: (a/b) * (c/d)
        // Cancel gcd(a,d) from a and d
        // Cancel gcd(b,c) from b and c
        $gcd1 = Integers::gcd($this->numerator, $other->denominator);
        $gcd2 = Integers::gcd($this->denominator, $other->numerator);

        $a = intdiv($this->numerator, $gcd1);
        $b = intdiv($this->denominator, $gcd2);
        $c = intdiv($other->numerator, $gcd2);
        $d = intdiv($other->denominator, $gcd1);

        // Now multiply the reduced terms: (a/b) * (c/d) = ac/bd
        $h = Integers::mul($a, $c);
        $k = Integers::mul($b, $d);

        return new self($h, $k);
    }

    /**
     * Divide this Rational by another value.
     *
     * @param self|int|float $other The value to divide by.
     * @return self A new Rational representing the quotient.
     * @throws DomainException If $other is a number outside the valid range for Rational.
     * @throws ArithmeticException If dividing by zero.
     * @throws OverflowException If the result overflows an integer.
     */
    public function div(self|int|float $other): self
    {
        // Get other value as a Rational.
        $other = self::toRational($other);

        // Guard against division by 0.
        if ($other->numerator === 0) {
            throw new ArithmeticException('Cannot divide by zero.');
        }

        return $this->mul($other->inv());
    }

    #endregion

    #region Power methods

    /**
     * Raise this Rational to an integer power.
     *
     * @param int $exponent The integer exponent.
     * @return self A new Rational representing the result.
     * @throws ArithmeticException If raising zero to a negative power.
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
        if ($this->numerator === 0) {
            // 0 to the power of a negative exponent is invalid (effectively division by zero).
            if ($exponent < 0) {
                throw new ArithmeticException('Cannot raise zero to a negative power.');
            }

            // 0 to the power of a positive exponent is 0.
            return new self(0);
        }

        // Handle exponent = 1. Any number to power 1 is itself.
        if ($exponent === 1) {
            return $this;
        }

        // Handle exponent = 2. Delegate to sqr().
        if ($exponent === 2) {
            return $this->sqr();
        }

        // Handle exponent = -1. Delegate to inv().
        if ($exponent === -1) {
            return $this->inv();
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
     * Find the integer closest to the Rational.
     *
     * The rounding method used here is "half away from zero", to match the default rounding mode
     * used by PHP's round() function. A future version of this method could include a RoundingMode
     * parameter.
     *
     * @return int The closest integer.
     */
    public function round(): int
    {
        if ($this->denominator === 1) {
            return $this->numerator;
        }

        $q = intdiv($this->numerator, $this->denominator);
        $r = $this->numerator % $this->denominator;

        // Round away from zero if remainder ≥ half denominator.
        if (abs($r) * 2 >= $this->denominator) {
            $result = $this->numerator > 0 ? $q + 1 : $q - 1;
        } else {
            $result = $q;
        }

        return $result;
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

    #region Serialization methods

    /**
     * Restore a Rational from serialized data.
     *
     * Reconstructs via the constructor, so the usual validation and canonicalization (reduction to lowest terms)
     * apply to unserialized data just as they do to normal construction. Without this method, PHP's default
     * unserialize() behavior would assign "numerator" and "denominator" directly as properties, bypassing both.
     *
     * There is no corresponding __serialize() method: unlike Complex (which has computed magnitude/phase properties
     * that must be excluded from the payload), Rational's only properties are numerator and denominator, so PHP's
     * default serialization already produces exactly the same payload a custom __serialize() would.
     *
     * Only "numerator" and "denominator" are read from $data; any other keys (e.g. from a hand-crafted string) are
     * ignored.
     *
     * @param array<string, mixed> $data The serialized data.
     * @throws DomainException If the data does not contain integer "numerator" and "denominator" values.
     * @throws ArithmeticException If the denominator is zero.
     * @throws UnderflowException If the value is non-zero but too small to represent as a Rational.
     * @throws OverflowException If the value is too large to represent as a Rational.
     */
    public function __unserialize(array $data): void
    {
        // Guard against missing values.
        if (!array_key_exists('numerator', $data) || !array_key_exists('denominator', $data)) {
            throw new DomainException(
                'Cannot unserialize Rational. Data must contain "numerator" and "denominator" values.'
            );
        }

        // Guard against non-integer values.
        if (!is_int($data['numerator']) || !is_int($data['denominator'])) {
            throw new DomainException(
                'Cannot unserialize Rational. Both "numerator" and "denominator" values must be integers.'
            );
        }

        // Call the constructor to validate, canonicalize, and set the values.
        $this->__construct($data['numerator'], $data['denominator']);
    }

    /**
     * Convert Rational to a value for JSON serialization.
     *
     * @return array{numerator: int, denominator: int} An associative array containing the numerator and denominator.
     */
    public function jsonSerialize(): array
    {
        return [
            'numerator'   => $this->numerator,
            'denominator' => $this->denominator,
        ];
    }

    #endregion

    #region Helper methods

    /**
     * Convert a value into a Rational if it isn't one already.
     *
     * This serves as a helper method used by many of the arithmetic methods in this class.
     *
     * @param self|int|float $value The value to convert.
     * @return self The equivalent Rational.
     * @throws DomainException If $value is non-finite or is outside the valid range for Rational.
     */
    private static function toRational(self|int|float $value): self
    {
        // Check for Rational.
        if ($value instanceof self) {
            return $value;
        }

        // Check for int. This will throw for PHP_INT_MIN, which is outside the valid range.
        if (is_int($value)) {
            return new self($value);
        }

        // Must be for float. This will throw for non-finite values or values outside the valid range.
        return self::fromFloat($value);
    }

    /**
     * Convert a fraction to its canonical form.
     *
     * @param int $num The numerator.
     * @param int $den The denominator.
     * @return list<int> The simplified numerator and denominator.
     * @throws DomainException If the numerator or denominator equals PHP_INT_MIN and the rational cannot be simplified.
     */
    private static function simplify(int $num, int $den): array
    {
        // Check for a numerator of zero.
        if ($num === 0) {
            return [0, 1];
        }

        // Check if the numerator and denominator are equal to each other.
        if ($num === $den) {
            return [1, 1];
        }

        // Check for a numerator equal to the negative of the denominator.
        if ($num === -$den) {
            return [-1, 1];
        }

        // We can accept PHP_INT_MIN (which is divisible by 2) if the other value is also divisible by 2.
        if (($num === PHP_INT_MIN && $den % 2 === 0) || ($num % 2 === 0 && $den === PHP_INT_MIN)) {
            $num = intdiv($num, 2);
            $den = intdiv($den, 2);
        }

        // Calculate the GCD. This will throw DomainException if either integer is PHP_INT_MIN.
        $gcd = Integers::gcd($num, $den);

        // Reduce the fraction if necessary.
        if ($gcd > 1) {
            // Neither of these calls to intdiv() will throw an exception because $gcd cannot be 0 or -1.
            $num = intdiv($num, $gcd);
            $den = intdiv($den, $gcd);
        }

        // Return the simplified fraction, ensuring the denominator is positive.
        return $den < 0 ? [-$num, -$den] : [$num, $den];
    }

    #endregion
}
