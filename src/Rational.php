<?php

declare(strict_types=1);

namespace OceanMoon\Math;

use DivisionByZeroError;
use DomainException;
use JsonSerializable;
use OceanMoon\Core\Exceptions\ConversionException;
use OceanMoon\Core\Floats;
use OceanMoon\Core\Integers;
use OceanMoon\Core\Numbers;
use OceanMoon\Core\Traits\Comparison\ApproxComparable;
use OverflowException;
use Override;
use Stringable;
use Throwable;
use UnderflowException;

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
     * @throws DivisionByZeroError If the denominator is zero.
     * @throws DomainException If the ratio involves PHP_INT_MIN in a way that cannot be exactly simplified (PHP_INT_MIN
     * paired with an odd or otherwise incompatible counterpart). This is not a magnitude problem — the resulting value
     * may well be within the representable range — it's specifically this exact integer ratio that cannot be reduced,
     * because PHP_INT_MIN cannot be safely negated. Use fromFloat() if an approximation is acceptable instead.
     */
    public function __construct(int $num = 0, int $den = 1)
    {
        // Check for zero denominator.
        if ($den === 0) {
            throw new DivisionByZeroError('Cannot create a Rational with a denominator of zero.');
        }

        try {
            // Simplify the ratio to canonical form.
            [$num, $den] = self::simplify($num, $den);
        } catch (DomainException) {
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
     * This method will throw an exception if the given value is outside that range.
     *
     * Float representation limits can cause inexact round-trip conversions for values very close to integers.
     *
     * @param float $value The float to convert.
     * @return self The equivalent (or closest approximating) Rational.
     * @throws ConversionException If the float could not be converted to a Rational.
     */
    public static function fromFloat(float $value): self
    {
        // Check for non-finite values.
        if (!is_finite($value)) {
            throw new ConversionException($value, self::class, 'Value must be a finite number.');
        }

        $absValue = abs($value);
        $sign = Numbers::sign($value, false);

        // Check if the absolute value of the float equals a valid integer.
        $i = Floats::tryConvertToInt($absValue);
        if ($i !== null) {
            return new self($sign * $i, 1);
        }

        // Check for values outside the valid range for Rational.
        $min = 1.0 / PHP_INT_MAX;
        $max = (float) PHP_INT_MAX;
        if ($absValue < $min) {
            throw new ConversionException($value, self::class, "Value $value is too small.");
        } elseif ($absValue > $max) {
            throw new ConversionException($value, self::class, "Value $value is too large.");
        }

        // Check for limits of range, which cannot be handled by the continued fraction algorithm.
        if ($absValue === $min) {
            return new self($sign, PHP_INT_MAX);
        } elseif ($absValue === $max) {
            return new self($sign * PHP_INT_MAX, 1);
        }

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

            // Calculate next convergent
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
     * Convert a string into a rational number.
     *
     * It will accept string values of the following form:
     * - int, e.g. "123", "-456"
     * - float, e.g. "123.456", "-456.789"
     * - fraction, e.g. "1/2", "-3/4"
     *
     * If the string represents a float, it will be converted to the closest rational number if it's within the valid
     * range.
     *
     * The input string is trimmed, including fraction parts. Therefore, the following examples are all allowed:
     * - " 123", "-456 ", etc.
     * - " 123.456", "-456.789 ", etc.
     * - " 1/2", "-3/4 ", " 5 / 6", etc.
     *
     * @param string $str The string to convert.
     * @return self A new Rational.
     * @throws ConversionException If the string could not be converted to a Rational.
     */
    public static function fromString(string $str): self
    {
        // Trim whitespace.
        $str = trim($str);

        // Handle empty string
        if ($str === '') {
            throw new ConversionException($str, self::class, 'String must not be empty.');
        }

        try {
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
        } catch (Throwable $e) {
            ConversionException::rethrow($str, self::class, $e);
        }

        throw new ConversionException($str, self::class, 'String has invalid format.');
    }

    /**
     * Convert a number or string into a Rational if it isn't one already.
     *
     * This serves as a helper method used by many of the arithmetic methods in this class, but may have utility
     * as a general-purpose conversion method elsewhere.
     *
     * @param mixed $value The number to convert.
     * @return self The equivalent Rational.
     * @throws ConversionException If the value could not be converted to a Rational.
     */
    public static function toRational(mixed $value): self
    {
        // Check for Rational.
        if ($value instanceof self) {
            return $value;
        }

        try {
            // Check for int.
            if (is_int($value)) {
                return new self($value);
            }

            // Check for float.
            if (is_float($value)) {
                return self::fromFloat($value);
            }

            // Check for string.
            if (is_string($value)) {
                return self::fromString($value);
            }
        } catch (Throwable $e) {
            ConversionException::rethrow($value, self::class, $e);
        }

        // The value has a type that cannot be converted to Rational.
        throw new ConversionException($value, self::class, 'Unsupported conversion.');
    }

    #endregion

    #region Conversion methods

    /**
     * Convert the rational number to a float.
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
     * Convert the rational number to a string. (Stringable implementation.)
     *
     * @return string The string representation of the rational number.
     */
    #[Override] // Stringable
    public function __toString(): string
    {
        return $this->numerator . ($this->denominator === 1 ? '' : '/' . $this->denominator);
    }

    #endregion

    #region Comparison methods

    /**
     * Compare a rational number with another number.
     *
     * @param mixed $other The number to compare with.
     * @return int Returns -1 if this < other, 0 if equal, 1 if this > other.
     * @throws ConversionException If the value cannot be converted to a Rational.
     */
    /** @disregard P1128 */
    #[Override] // Comparable
    public function compare(mixed $other): int
    {
        // Convert int to Rational or float.
        if (is_int($other)) {
            try {
                $other = new self($other);
            } catch (Throwable) {
                $other = (float) $other;
            }
        }

        // If $other is still a number, then either it's not a whole number or it's outside the valid range.
        // In this case it's quicker to compare $this and $other as floats than it would be to convert the value to a
        // Rational via fromFloat().
        if (Numbers::isNumber($other)) {
            $left = $this->toFloat();
            $right = $other;
        } else {
            // Get other value as a Rational.
            $other = self::toRational($other);

            // If the denominators are equal, just compare numerators.
            if ($this->denominator === $other->denominator) {
                $left = $this->numerator;
                $right = $other->numerator;
            } else {
                try {
                    // Cross multiply: compare a*d with b*c for a/b vs c/d.
                    $left = Integers::mul($this->numerator, $other->denominator);
                    $right = Integers::mul($this->denominator, $other->numerator);
                } catch (OverflowException) {
                    // In case of overflow, compare equivalent floating point values.
                    // NB: This could produce a result of 0 (equal) if two different rationals convert to the same
                    // float, which is possible for values with a magnitude greater than or equal to 2^53 (64-bit
                    // platforms only). But that should be ok.
                    $left = $this->toFloat();
                    $right = $other->toFloat();
                }
            }
        }

        // Use the spaceship operator to compare. Note, the spaceship operator only guarantees sign, not specific
        // values, so we call Numbers::sign to normalize the result to -1, 0, or 1 for predictable behavior used by
        // other comparison methods.
        return Numbers::sign($left <=> $right);
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
     * @throws ConversionException If the value cannot be converted to a Rational.
     * @see Floats::approxEqual() For the tolerance algorithm details.
     */
    /** @disregard P1128 */
    #[Override] // ApproxEquatable
    public function approxEqual(
        mixed $other,
        float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
        float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
    ): bool {
        // Get the other value as a number, if not already.
        if (!Numbers::isNumber($other)) {
            $other = self::toRational($other)->toFloat();
        }

        // Compare as floats.
        return Floats::approxEqual($this->toFloat(), $other, $relTol, $absTol);
    }

    #endregion

    #region Unary arithmetic methods

    /**
     * Calculate the absolute value of this rational number.
     *
     * @return self A new rational number representing the absolute value.
     */
    public function abs(): self
    {
        return new self(abs($this->numerator), $this->denominator);
    }

    /**
     * Calculate the negative of this rational number.
     *
     * @return self A new rational number representing the negative.
     */
    public function neg(): self
    {
        return new self(-$this->numerator, $this->denominator);
    }

    /**
     * Calculate the reciprocal of this rational number.
     *
     * @return self A new rational number representing the reciprocal.
     * @throws DivisionByZeroError If the value is zero.
     */
    public function inv(): self
    {
        // Guard.
        if ($this->numerator === 0) {
            throw new DivisionByZeroError('Cannot take reciprocal of zero.');
        }

        // Preserve sign: if num is negative, swap and negate.
        return $this->numerator > 0
            ? new self($this->denominator, $this->numerator)
            : new self(-$this->denominator, -$this->numerator);
    }

    #endregion

    #region Binary arithmetic methods

    /**
     * Add another value to this rational number.
     *
     * @param int|float|self $other The value to add.
     * @return self A new rational number representing the sum.
     * @throws ConversionException If the value cannot be converted to a Rational.
     * @throws OverflowException If the result overflows an integer.
     */
    public function add(int|float|self $other): self
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
     * Subtract another value from this rational number.
     *
     * @param int|float|self $other The value to subtract.
     * @return self A new rational number representing the difference.
     * @throws ConversionException If the value cannot be converted to a Rational.
     * @throws OverflowException If the result overflows an integer.
     */
    public function sub(int|float|self $other): self
    {
        // Get other value as a Rational.
        $other = self::toRational($other);

        return $this->add($other->neg());
    }

    /**
     * Multiply this rational number by another value.
     *
     * @param int|float|self $other The value to multiply by.
     * @return self A new rational number representing the product.
     * @throws ConversionException If the value cannot be converted to a Rational.
     * @throws OverflowException If the result overflows an integer.
     */
    public function mul(int|float|self $other): self
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
     * Divide this rational number by another value.
     *
     * @param int|float|self $other The value to divide by.
     * @return self A new rational number representing the quotient.
     * @throws ConversionException If the value cannot be converted to a Rational.
     * @throws DivisionByZeroError If dividing by zero.
     * @throws OverflowException If the result overflows an integer.
     */
    public function div(int|float|self $other): self
    {
        // Get other value as a Rational.
        $other = self::toRational($other);

        // Guard against division by 0.
        if ($other->numerator === 0) {
            throw new DivisionByZeroError('Cannot divide by zero.');
        }

        return $this->mul($other->inv());
    }

    #endregion

    #region Power methods

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
        if ($this->numerator === 0) {
            // 0 to the power of a negative exponent is invalid (effectively division by zero).
            if ($exponent < 0) {
                throw new DomainException('Cannot raise zero to a negative power.');
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
     * Square this rational number.
     *
     * Equivalent to pow(2), but more efficient and readable.
     *
     * @return self A new rational number representing the square of this number.
     */
    public function sqr(): self
    {
        return $this->mul($this);
    }

    #endregion

    #region Rounding methods

    /**
     * Find the integer closest to the rational number.
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
     * Find the closest integer less than or equal to the rational number.
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
     * Find the closest integer greater than or equal to the rational number.
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
     * @throws DivisionByZeroError If the denominator is zero.
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
