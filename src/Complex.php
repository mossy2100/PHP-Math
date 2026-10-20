<?php

declare(strict_types=1);

namespace OceanMoon\Math;

use ArrayAccess;
use DomainException;
use InvalidArgumentException;
use LogicException;
use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Core\Exceptions\FormatException;
use OceanMoon\Core\Floats;
use OceanMoon\Core\Numbers;
use OceanMoon\Core\Stringify;
use OceanMoon\Core\Traits\Comparison\ApproxEquatable;
use OutOfRangeException;
use OverflowException;
use Override;
use RoundingMode;
use Stringable;

use const OceanMoon\Core\M_TAU;

/**
 * Encapsulates a complex number and provides a number of useful methods.
 *
 * @implements ArrayAccess<int, float>
 */
final class Complex implements Stringable, ArrayAccess
{
    use ApproxEquatable;

    #region Constants

    // Constants for the inverse trigonometric and hyperbolic functions. See calcAsinh().

    /**
     * Below this value of A, ln(A + √(A² - 1)) is calculated with log1p() (Hull et al. suggest 1.5; 10 works better).
     */
    private const float A_CROSSOVER = 10.0;

    /**
     * Above this value of B, asin(B) is calculated with atan2(), as suggested by Hull et al.
     */
    private const float B_CROSSOVER = 0.6417;

    /**
     * At least 4·√PHP_FLOAT_MIN.
     */
    private const float FOUR_SQRT_MIN = 2 ** -509;

    /**
     * At most √PHP_FLOAT_MAX / 4.
     */
    private const float QUARTER_SQRT_MAX = 2 ** 509;

    /**
     * At least √PHP_FLOAT_MIN.
     */
    private const float SQRT_MIN = 2 ** -511;

    /**
     * Above this magnitude, a part is so large that the asymptotic formulas are exact to within rounding.
     */
    private const float RECIP_EPSILON = 1 / PHP_FLOAT_EPSILON;

    /**
     * √(3·PHP_FLOAT_EPSILON).
     */
    private const float SQRT_3_EPSILON = 2.5809568279517849e-8;

    /**
     * √(6·PHP_FLOAT_EPSILON).
     */
    private const float SQRT_6_EPSILON = 3.6500241499888571e-8;

    /**
     * The rounding error in M_PI_2, i.e. π/2 - M_PI_2.
     */
    private const float PI_2_LO = 6.1232339957367659e-17;

    #endregion

    #region Properties

    /**
     * The real part of the complex number.
     */
    private(set) float $real;

    /**
     * The imaginary part of the complex number.
     */
    private(set) float $imaginary;

    /**
     * The magnitude (a.k.a. absolute value or modulus) of this complex number.
     */
    private(set) float $magnitude;

    /**
     * The phase (a.k.a. argument) of this complex number in radians.
     *
     * The value is normalized to the range (-π, π]. This is known as the principal value.
     * @see https://en.wikipedia.org/wiki/Principal_value#Complex_argument
     */
    private(set) float $phase;

    #endregion

    #region Constructor

    /**
     * Create a new complex number.
     *
     * Negative zeros are stored as positive zeros, so values that are equal always give the same results.
     *
     * @param float $real The real part.
     * @param float $imag The imaginary part.
     * @throws DomainException If either part is not finite (±INF or NAN).
     * @throws OverflowException If the magnitude is too large to represent as a float.
     */
    public function __construct(float $real = 0, float $imag = 0)
    {
        // Check for non-finite values.
        if (!is_finite($real)) {
            throw new DomainException(Stringify::prepEx('Cannot create Complex with non-finite real part: ?.', $real));
        }
        if (!is_finite($imag)) {
            throw new DomainException(
                Stringify::prepEx('Cannot create Complex with non-finite imaginary part: ?.', $imag)
            );
        }

        // Set the properties.
        $this->real = Floats::normalizeZero($real);
        $this->imaginary = Floats::normalizeZero($imag);

        if ($imag === 0.0) {
            $this->magnitude = abs($real);
            $this->phase = $real < 0 ? M_PI : 0.0;
        } else {
            $this->magnitude = hypot($real, $imag);
            // Both parts are finite, but the magnitude can still overflow when both are close to the float limit.
            if (!is_finite($this->magnitude)) {
                throw new OverflowException(Stringify::prepEx(
                    'Cannot create Complex with real part ? and imaginary part ?. Its magnitude would overflow.',
                    $real,
                    $imag
                ));
            }
            // The phase must be in the range (-π, π].
            // In this branch, we know the imaginary part is not 0, so the result of atan2() should theoretically never
            // equal π or -π. In practice, however, it can be, if the imaginary part is very close to 0.
            // So, we call wrap().
            $this->phase = Floats::wrap(atan2($imag, $real));
        }
    }

    #endregion

    #region Factory methods

    /**
     * Create a Complex from a string.
     *
     * Supports various formats:
     * - Real numbers: "5", "-3.14", "0"
     * - Pure imaginary: "i", "-i", "3i", "-2.5i"
     * - Complex: "3+4i", "5-2i", "-1+i", "2.5-3.7i"
     * - Spaces allowed: "3 + 4i", "5 - 2i"
     * - Either order: "4i+3", "-2i+5"
     *
     * @param string $str The string to convert.
     * @return self The equivalent Complex.
     * @throws FormatException If the string does not represent a valid Complex.
     * @throws DomainException If an out-of-range exponent makes a part non-finite (e.g. '1e400').
     */
    public static function fromString(string $str): self
    {
        // Trim whitespace.
        $str = trim($str);

        // Handle empty string
        if ($str === '') {
            throw new FormatException('Cannot convert empty string to Complex.');
        }

        $rxNum = '(?:\d+\.?\d*|\.\d+)(?:[eE][+-]?\d+)?';
        if (preg_match("/^([+-]?)($rxNum)$/", $str, $matches)) {
            // Pattern: ±a (real only)
            [$entire, $realSign, $realVal] = $matches;
            $imagSign = '';
            $imagVal = 0;
        } elseif (preg_match("/^([+-]?)((?:$rxNum)?)i$/", $str, $matches)) {
            // Pattern: ±bi (imaginary only)
            [$entire, $imagSign, $imagVal] = $matches;
            $realSign = '';
            $realVal = 0;
        } elseif (preg_match("/^([+-]?)($rxNum)\s*([+-])\s*((?:$rxNum)?)i$/", $str, $matches)) {
            // Pattern: ±a ± bi (real + imag)
            [$entire, $realSign, $realVal, $imagSign, $imagVal] = $matches;
        } elseif (preg_match("/^([+-]?)((?:$rxNum)?)i\s*([+-])\s*($rxNum)$/", $str, $matches)) {
            // Pattern: ±bi ± a (imag + real)
            [$entire, $imagSign, $imagVal, $realSign, $realVal] = $matches;
        } else {
            // Include the string in the message, abbreviated, unless it isn't valid UTF-8, which Stringify can't
            // show.
            throw new FormatException(
                mb_check_encoding($str, 'UTF-8')
                    ? Stringify::prepEx('Cannot convert string ? to Complex. Invalid format.', $str)
                    : 'Cannot convert string to Complex. Invalid format.'
            );
        }

        // Get the real part.
        $real = ($realSign === '-' ? -1 : 1) * (float) $realVal;

        // Get the imaginary part. Handle cases where the imaginary coefficient is omitted (like +i or -i).
        $imag = ($imagSign === '-' ? -1 : 1) * ($imagVal === '' ? 1.0 : (float) $imagVal);

        // Construct the Complex. This will throw if either value is non-finite, which can happen with out-of-range
        // exponents (e.g. '1e400').
        return new self($real, $imag);
    }

    /**
     * Create a complex number from polar coordinates.
     *
     * @param float $mag The magnitude (distance from origin).
     * @param float $phase The phase angle in radians.
     * @return self The equivalent Complex.
     * @throws DomainException If either the magnitude or phase are non-finite, or the magnitude is negative.
     */
    public static function fromPolar(float $mag, float $phase): self
    {
        // Check for non-finite values.
        if (!is_finite($mag)) {
            throw new DomainException(Stringify::prepEx('Cannot create Complex with non-finite magnitude: ?.', $mag));
        }
        if (!is_finite($phase)) {
            throw new DomainException(Stringify::prepEx('Cannot create Complex with non-finite phase: ?.', $phase));
        }

        // Check for valid magnitude.
        if ($mag < 0) {
            throw new DomainException(Stringify::prepEx('Cannot create Complex with negative magnitude: ?.', $mag));
        }

        // Construct the new Complex.
        return new self($mag * cos($phase), $mag * sin($phase));
    }

    #endregion

    #region Conversion methods

    /**
     * Convert the complex number to a string representation.
     *
     * @return string String representation in the form "a", "bi", "a + bi", or "a - bi".
     */
    #[Override] // Stringable
    public function __toString(): string
    {
        // Handle case for 0 imaginary part.
        if ($this->isReal()) {
            return (string) $this->real;
        }

        // Handle case for 0 real part and non-zero imaginary part.
        if ($this->real === 0.0) {
            if ($this->imaginary === 1.0) {
                return 'i';
            }
            if ($this->imaginary === -1.0) {
                return '-i';
            }
            return $this->imaginary . 'i';
        }

        // Construct the string for the a + bi or a - bi form.
        $abs = abs($this->imaginary);
        return $this->real . ' ' . ($this->imaginary > 0 ? '+' : '-') . ' ' . ($abs === 1.0 ? '' : $abs) . 'i';
    }

    #endregion

    #region Inspection methods

    /**
     * Check if a complex number is real.
     *
     * @return bool True if the Complex is a real number, otherwise false.
     */
    public function isReal(): bool
    {
        return $this->imaginary === 0.0;
    }

    #endregion

    #region Comparison methods

    /**
     * Check if this Complex is equal to another.
     *
     * @param mixed $other The value to compare with.
     * @return bool True if the values are equal.
     * @throws InvalidArgumentException If $other is not Complex, int, or float.
     * @throws DomainException If $other is NAN. There's no meaningful answer for NAN, unlike ±INF, which a Complex
     * is simply never equal to.
     */
    /** @disregard P1128 */
    #[Override] // Equatable
    public function equal(mixed $other): bool
    {
        // Check type.
        if (!$other instanceof self && !Numbers::isNumber($other)) {
            throw new InvalidArgumentException(
                'Cannot compare Complex with ' . get_debug_type($other) . '. Must be Complex, int, or float.'
            );
        }

        if (is_float($other)) {
            // Fail on NAN - no meaningful result.
            if (is_nan($other)) {
                throw new DomainException('Cannot compare Complex with NAN.');
            }

            // A Complex (always finite) is never equal to ±INF.
            if (!is_finite($other)) {
                return false;
            }
        }

        // Get other value as a Complex.
        if (Numbers::isNumber($other)) {
            $other = new self($other);
        }

        // Compare real and imaginary parts.
        return $this->real === $other->real && $this->imaginary === $other->imaginary;
    }

    /**
     * Check if this complex number is approximately equal to another value, which may be Complex, int or float.
     *
     * The comparison will use the absolute tolerance first, and if that fails, the relative tolerance.
     * To compare purely by absolute difference, set the relative tolerance to zero.
     * To compare purely by relative difference, set the absolute tolerance to zero.
     * @see Floats::approxEqual()
     *
     * @param mixed $other The value to compare with.
     * @param float $relTol The relative tolerance.
     * @param float $absTol The absolute tolerance.
     * @return bool True if the numbers are equal within the given tolerances, otherwise false.
     * @throws InvalidArgumentException If $other is not Complex, int, or float.
     * @throws DomainException If $other is NAN. There's no meaningful answer for NAN, unlike ±INF, which a Complex
     * is simply never (approximately) equal to.
     */
    #[Override] // ApproxEquatable
    public function approxEqual(
        mixed $other,
        float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
        float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
    ): bool {
        // Check type.
        if (!$other instanceof self && !Numbers::isNumber($other)) {
            throw new InvalidArgumentException(
                'Cannot compare Complex with ' . get_debug_type($other) . '. Must be Complex, int, or float.'
            );
        }

        if (is_float($other)) {
            // Fail on NAN - no meaningful result.
            if (is_nan($other)) {
                throw new DomainException('Cannot compare Complex with NAN.');
            }

            // A Complex (always finite) is never approximately equal to ±INF.
            if (!is_finite($other)) {
                return false;
            }
        }

        // Get other value as a Complex.
        if (Numbers::isNumber($other)) {
            $other = new self($other);
        }

        // Compare real and imaginary parts.
        return Floats::approxEqual($this->real, $other->real, $relTol, $absTol) &&
            Floats::approxEqual($this->imaginary, $other->imaginary, $relTol, $absTol);
    }

    #endregion

    #region Unary arithmetic methods

    /**
     * Negate a complex number.
     *
     * @return self A new complex number representing the negative of this one.
     */
    public function neg(): self
    {
        return new self(-$this->real, -$this->imaginary);
    }

    /**
     * Calculate the reciprocal of this complex number.
     *
     * @return self A new complex number representing the reciprocal.
     * @throws ArithmeticException If this Complex is zero.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function recip(): self
    {
        // Check for division by zero.
        if ($this->equal(0)) {
            throw new ArithmeticException('Cannot divide by zero.');
        }

        return self::calcWithOverflowMessage('invert the given Complex', fn () => new self(1)->div($this));
    }

    /**
     * Get the complex conjugate of this number.
     *
     * @return self A new complex number representing the conjugate.
     */
    public function conj(): self
    {
        return new self($this->real, -$this->imaginary);
    }

    #endregion

    #region Binary arithmetic methods

    /**
     * Add another complex number to this one.
     *
     * @param self|float $other The real or complex number to add.
     * @return self A new complex number representing the sum.
     * @throws DomainException If $other is a non-finite float (±INF or NAN).
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function add(self|float $other): self
    {
        // Convert float to Complex.
        if (is_float($other)) {
            $other = new self($other);
        }

        // Do the addition.
        return self::fromResult(
            $this->real + $other->real,
            $this->imaginary + $other->imaginary,
            'add the given Complex numbers'
        );
    }

    /**
     * Subtract another complex number from this one.
     *
     * @param self|float $other The real or complex number to subtract.
     * @return self A new complex number representing the difference.
     * @throws DomainException If $other is a non-finite float (±INF or NAN).
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function sub(self|float $other): self
    {
        // Convert float to Complex.
        if (is_float($other)) {
            $other = new self($other);
        }

        // Do the subtraction.
        return self::fromResult(
            $this->real - $other->real,
            $this->imaginary - $other->imaginary,
            'subtract the given Complex numbers'
        );
    }

    /**
     * Multiply this complex number by another.
     * Uses the formula: (a + bi)(c + di) = (ac - bd) + (ad + bc)i
     *
     * @param self|float $other The real or complex number to multiply by.
     * @return self A new complex number representing the product.
     * @throws DomainException If $other is a non-finite float (±INF or NAN).
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function mul(self|float $other): self
    {
        // Convert float to Complex.
        if (is_float($other)) {
            $other = new self($other);
        }

        // Do the multiplication.
        $a = $this->real;
        $b = $this->imaginary;
        $c = $other->real;
        $d = $other->imaginary;
        return self::fromResult($a * $c - $b * $d, $a * $d + $b * $c, 'multiply the given Complex numbers');
    }

    /**
     * Divide this complex number by another.
     *
     * Uses Smith's algorithm, which divides by the larger of the divisor's components first. The textbook formula,
     * (a + bi)/(c + di) = [(ac + bd) + (bc - ad)i]/(c² + d²), squares the divisor's components, so c² + d² underflows
     * to zero or overflows to INF for a divisor whose magnitude is outside about 1e-154 to 1e154, even when the result
     * is representable.
     *
     * @param self|float $other The real or complex number to divide by.
     * @return self A new complex number representing the quotient.
     * @throws DomainException If $other is a non-finite float (±INF or NAN).
     * @throws ArithmeticException If the divisor is zero.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function div(self|float $other): self
    {
        // Convert float to Complex.
        if (is_float($other)) {
            $other = new self($other);
        }

        // Check for division by zero.
        if ($other->equal(0)) {
            throw new ArithmeticException('Cannot divide by zero.');
        }

        // Do the division, using Smith's algorithm.
        $a = $this->real;
        $b = $this->imaginary;
        $c = $other->real;
        $d = $other->imaginary;
        if (abs($c) >= abs($d)) {
            $r = $d / $c;
            $den = $c + $d * $r;
            $real = ($a + $b * $r) / $den;
            $imag = ($b - $a * $r) / $den;
        } else {
            $r = $c / $d;
            $den = $c * $r + $d;
            $real = ($a * $r + $b) / $den;
            $imag = ($b * $r - $a) / $den;
        }

        return self::fromResult($real, $imag, 'divide the given Complex numbers');
    }

    #endregion

    #region Power methods

    /**
     * Raise this complex number to a power.
     * This function can be multivalued for certain base/exponent combinations.
     * For simplicity, only the principal value is returned.
     *
     * Single-valued cases:
     * - Any base raised to an integer exponent.
     * - Real positive base with real exponent.
     *
     * A real integer exponent is computed exactly via exponentiation by squaring, rather than the general
     * e^(w * ln z) formula, for better precision and performance.
     *
     * Multivalued cases:
     * - Complex base with fractional exponent: z^(1/n)
     * - Negative real base with fractional exponent: (-2)^(1/3)
     * - Any base with complex exponent: z^(a+bi) where b ≠ 0
     *
     * @param self|float $exp The real or complex number to raise this complex number to.
     * @return self A new complex number representing the result.
     * @throws DomainException If $exp is a non-finite float (±INF or NAN).
     * @throws ArithmeticException If attempting to raise 0 to a negative or complex power.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function pow(self|float $exp): self
    {
        // Convert float to Complex.
        if (is_float($exp)) {
            $exp = new self($exp);
        }

        // Handle exponent = 0. Any number to power 0 is 1.
        // Although mathematically 0^0 is undefined, we return 1 for consistency with pow(0, 0).
        // This is a common result in many programming languages and hence follows the Principle of Least Astonishment.
        // @see https://en.wikipedia.org/wiki/Zero_to_the_power_of_zero
        if ($exp->equal(0)) {
            return new self(1);
        }

        // Handle base = 0.
        if ($this->equal(0)) {
            // Check for complex exponent.
            if (!$exp->isReal()) {
                throw new ArithmeticException('Cannot raise zero to a complex power.');
            }

            // Check for negative real exponent.
            if ($exp->real < 0) {
                throw new ArithmeticException('Cannot raise zero to a negative power.');
            }

            // The exponent is a positive real number. 0 raised to any positive real number is 0.
            return new self();
        }

        // Handle exponent = 1. Any number to power 1 is itself.
        if ($exp->equal(1)) {
            return clone $this;
        }

        return self::calcWithOverflowMessage(
            'raise the given Complex to the given power',
            fn () => $this->calcPow($exp)
        );
    }

    /**
     * Square this complex number.
     *
     * Equivalent to pow(2), but more readable as a standalone call.
     *
     * @return self A new complex number representing the square of this number.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function sqr(): self
    {
        return self::calcWithOverflowMessage('square the given Complex', fn () => $this->mul($this));
    }

    #endregion

    #region Root methods

    /**
     * Calculate roots of this complex number using De Moivre's theorem.
     *
     * Roots that lie on an axis are exact, e.g. the 4th roots of 1 are exactly 1, i, -1 and -i. The square roots are
     * sqrt() and its negative.
     *
     * @param int $degree The degree of the root to calculate (e.g. 2 for square root, 3 for cube root).
     * @return list<self> An array of $degree Complex numbers representing the roots.
     * @throws DomainException If the degree is not a positive integer.
     */
    public function roots(int $degree): array
    {
        // Check for negative degree.
        if ($degree <= 0) {
            throw new DomainException("Cannot compute roots of degree: $degree. Must be positive.");
        }

        // Handle special case of 0.
        if ($this->equal(0)) {
            return [
                new self(),
            ];
        }

        // Handle degree 1.
        if ($degree === 1) {
            return [
                clone $this,
            ];
        }

        // Handle degree 2. Use sqrt(), so the results agree.
        if ($degree === 2) {
            $root = $this->sqrt();
            return [
                $root,
                $root->neg(),
            ];
        }

        // Calculate the magnitude of the roots. Raising to the power 1/n is inexact, as 1/n is rounded, e.g.
        // 64 ** (1/3) is 3.9999999999999996. So, refine it with one step of Newton's method, keeping whichever is
        // closer.
        $rootMag = $this->magnitude ** (1.0 / $degree);
        $refined = $rootMag - ($rootMag ** $degree - $this->magnitude) / ($degree * $rootMag ** ($degree - 1));
        if (abs($refined ** $degree - $this->magnitude) < abs($rootMag ** $degree - $this->magnitude)) {
            $rootMag = $refined;
        }

        // If this number lies on an axis, get its phase as a whole number of quarter turns.
        $quarterTurns = null;
        if ($this->imaginary === 0.0) {
            $quarterTurns = $this->real > 0 ? 0 : 2;
        } elseif ($this->real === 0.0) {
            $quarterTurns = $this->imaginary > 0 ? 1 : -1;
        }

        // Calculate all n roots.
        $roots = [];
        $theta = $this->phase / $degree;
        $delta = M_TAU / $degree;
        for ($k = 0; $k < $degree; $k++) {
            // A root that lies on an axis is the root magnitude times 1, i, -1 or -i, so calculate it exactly.
            if ($quarterTurns !== null && ($quarterTurns + 4 * $k) % $degree === 0) {
                $roots[] = match (intdiv($quarterTurns + 4 * $k, $degree) % 4) {
                    0       => new self($rootMag),
                    1       => new self(0, $rootMag),
                    2       => new self(-$rootMag),
                    default => new self(0, -$rootMag),
                };
                continue;
            }

            $rootPhase = $theta + $k * $delta;
            $roots[] = self::fromPolar($rootMag, $rootPhase);
        }

        return $roots;
    }

    /**
     * Calculate the square root of this complex number.
     * Only the principal value is returned. For both square roots, call roots(2).
     *
     * Uses a numerically stable formula rather than the polar form, so the result is exact on the axes, e.g. the
     * square root of -4 is exactly 2i.
     *
     * @return self A new complex number representing the square root of this number.
     */
    public function sqrt(): self
    {
        // Handle special case of 0.
        if ($this->equal(0)) {
            return new self();
        }

        // With z = a + bi, calculate t = √((|z| + |a|) / 2).
        $absReal = abs($this->real);
        $sum = $this->magnitude + $absReal;
        if (is_infinite($sum)) {
            // Scale down to avoid overflow.
            $t = 2 * sqrt($this->magnitude / 8 + $absReal / 8);
        } elseif ($sum < 2 * PHP_FLOAT_MIN) {
            // Scale up, as halving a subnormal number loses precision.
            $t = sqrt(2 * $sum) / 2;
        } else {
            $t = sqrt($sum / 2);
        }

        // If a ≥ 0, √z = t + (b / 2t)i.
        if ($this->real >= 0) {
            return new self($t, $this->imaginary === 0.0 ? 0.0 : $this->imaginary / (2 * $t));
        }

        // If a < 0, √z = |b| / 2t ± ti, taking the sign of b. As with the phase, a zero b counts as positive.
        return new self(abs($this->imaginary) / (2 * $t), $this->imaginary < 0 ? -$t : $t);
    }

    #endregion

    #region Transcendental methods

    /**
     * Calculate e^z where z is this complex number.
     *
     * @return self A new complex number representing e^z.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function exp(): self
    {
        // Use shortcuts where possible.
        if ($this->equal(0)) {
            return new self(1);
        }

        if ($this->equal(1)) {
            return new self(M_E);
        }

        if ($this->equal(M_LN2)) {
            return new self(2);
        }

        if ($this->equal(M_LNPI)) {
            return new self(M_PI);
        }

        if ($this->equal(M_LN10)) {
            return new self(10);
        }

        // Eulerian identities.
        // e^iπ = -1
        if ($this->equal(new self(0, M_PI))) {
            return new self(-1);
        }
        // e^iτ = 1
        if ($this->equal(new self(0, M_TAU))) {
            return new self(1);
        }

        // General solution. Uses Euler's formula: e^(a + bi) = e^a * (cos(b) + i*sin(b)).
        // Calling fromPolar(mag, phase) builds a new Complex via mag * (cos(phase) + i*sin(phase)), so passing
        // magnitude = e^a and phase = b implements the formula above.
        $mag = exp($this->real);
        if (!is_finite($mag)) {
            throw new OverflowException('Cannot compute exponential of the given Complex. The result would overflow.');
        }
        return self::fromPolar($mag, $this->imaginary);
    }

    /**
     * Calculate the natural logarithm of a complex number.
     *
     * @return self A new complex number representing ln(z).
     * @throws ArithmeticException If the complex number is 0.
     */
    public function ln(): self
    {
        // Check for ln(0), which is undefined.
        if ($this->equal(0)) {
            throw new ArithmeticException('Cannot compute logarithm of zero.');
        }

        // Use shortcuts where possible.
        if ($this->equal(1)) {
            return new self(0);
        }

        if ($this->equal(2)) {
            return new self(M_LN2);
        }

        if ($this->equal(M_E)) {
            return new self(1);
        }

        if ($this->equal(M_PI)) {
            return new self(M_LNPI);
        }

        if ($this->equal(10)) {
            return new self(M_LN10);
        }

        // General solution. Calculate ln(z) = ln|z| + i*arg(z)
        return new self(log($this->magnitude), $this->phase);
    }

    /**
     * Calculate the logarithm of a complex number with the given base.
     * Uses the change of base formula: log_b(z) = ln(z) / ln(b)
     *
     * Negative real arguments are supported: they return the principal complex value (e.g. log_2(-8) = 3 + iπ/ln 2).
     *
     * @param self|float $base The base for the logarithm.
     * @return self A new complex number representing log_b(z).
     * @throws DomainException If the base is a non-finite float (±INF or NAN).
     * @throws ArithmeticException If the base equals 0 or 1, or if this number is 0.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function log(self|float $base): self
    {
        // Convert float to Complex.
        if (is_float($base)) {
            $base = new self($base);
        }

        // Check for invalid base values.
        if ($base->equal(0)) {
            throw new ArithmeticException('Cannot compute logarithm with base zero.');
        }
        if ($base->equal(1)) {
            throw new ArithmeticException('Cannot compute logarithm with base one.');
        }

        // Handle $this = 0.
        if ($this->equal(0)) {
            throw new ArithmeticException('Cannot compute logarithm of zero.');
        }

        // Check for natural logarithm.
        if ($base->equal(M_E)) {
            return $this->ln();
        }

        // Use built-in constants for log_2(e) and log_10(e).
        if ($this->equal(M_E)) {
            if ($base->equal(2)) {
                return new self(M_LOG2E);
            }

            if ($base->equal(10)) {
                return new self(M_LOG10E);
            }
        }

        // Use PHP's built-in real log() only when both arguments are positive reals, the only case where it returns
        // the principal value. A negative real $this (e.g. log_2(-8) = 3 + iπ/ln 2) or a negative real $base has a
        // valid complex result, so those fall through to the general solution below; PHP's real log() would
        // otherwise return NAN for the former, or throw a raw ValueError for the latter.
        if ($this->isReal() && $base->isReal() && $this->real > 0 && $base->real > 0) {
            return new self(log($this->real, $base->real));
        }

        // General solution. Compute log_b(z) = ln(z) / ln(b)
        return self::calcWithOverflowMessage(
            'compute logarithm of the given Complex',
            fn () => $this->ln()->div($base->ln())
        );
    }

    #endregion

    #region Trigonometric methods

    /**
     * Calculate the sine of this complex number.
     *
     * @return self A new complex number representing the sine of this complex number.
     * @throws OverflowException If the result is too large to represent as a float.
     * @see https://en.wikipedia.org/wiki/Trigonometric_functions#In_the_complex_plane
     */
    public function sin(): self
    {
        // sin(z) = sin(x)cosh(y) + i·cos(x)sinh(y)
        // where z = x + iy
        $x = $this->real;
        $y = $this->imaginary;
        return self::fromResult(sin($x) * cosh($y), cos($x) * sinh($y), 'compute sine of the given Complex');
    }

    /**
     * Calculate the cosine of this complex number.
     *
     * @return self A new complex number representing the cosine of this complex number.
     * @throws OverflowException If the result is too large to represent as a float.
     * @see https://en.wikipedia.org/wiki/Trigonometric_functions#In_the_complex_plane
     */
    public function cos(): self
    {
        // cos(z) = cos(x)cosh(y) - i·sin(x)sinh(y)
        // where z = x + iy
        $x = $this->real;
        $y = $this->imaginary;
        return self::fromResult(cos($x) * cosh($y), -sin($x) * sinh($y), 'compute cosine of the given Complex');
    }

    /**
     * Calculate the tangent of this complex number.
     *
     * Calculated as -i·tanh(iz), so it never overflows. For a large imaginary part, the result is about ±i.
     *
     * @return self A new complex number representing the tangent of this complex number.
     * @see https://en.wikipedia.org/wiki/Trigonometric_functions#In_the_complex_plane
     */
    public function tan(): self
    {
        // tan(z) = -i·tanh(iz), where iz = -y + ix. If tanh(iz) = u + iv, then tan(z) = v - iu.
        [$u, $v] = self::calcTanh(-$this->imaginary, $this->real);
        return new self($v, -$u);
    }

    #endregion

    #region Inverse trigonometric methods

    /**
     * Calculate the inverse sine of this complex number.
     *
     * The result is the principal value, accurate to within a few ulps for any argument. On a branch cut (the real
     * axis outside [-1, 1]), the sign of the imaginary part, including a signed zero, chooses the side, as in C99.
     *
     * @return self A new complex number representing the inverse sine of this complex number.
     * @see https://en.wikipedia.org/wiki/Inverse_trigonometric_functions#Extension_to_the_complex_plane
     */
    public function asin(): self
    {
        // asin(x + iy) is asinh(y + ix) with its parts swapped.
        [$real, $imag] = self::calcAsinh($this->imaginary, $this->real);
        return new self($imag, $real);
    }

    /**
     * Calculate the inverse cosine of this complex number.
     *
     * The result is the principal value, accurate to within a few ulps for any argument. On a branch cut (the real
     * axis outside [-1, 1]), the sign of the imaginary part, including a signed zero, chooses the side, as in C99.
     *
     * @return self A new complex number representing the inverse cosine of this complex number.
     * @see https://en.wikipedia.org/wiki/Inverse_trigonometric_functions#Extension_to_the_complex_plane
     */
    public function acos(): self
    {
        [$real, $imag] = self::calcAcos($this->real, $this->imaginary);
        return new self($real, $imag);
    }

    /**
     * Calculate the inverse tangent of this complex number.
     *
     * The result is the principal value, accurate to within a few ulps for any argument. On a branch cut (the
     * imaginary axis outside [-i, i]), the sign of the real part, including a signed zero, chooses the side, as in C99.
     *
     * @return self A new complex number representing the inverse tangent of this complex number.
     * @throws ArithmeticException If this Complex is i or -i, where the inverse tangent is undefined.
     * @see https://en.wikipedia.org/wiki/Inverse_trigonometric_functions#Extension_to_the_complex_plane
     */
    public function atan(): self
    {
        // atan(z) is undefined at z = ±i.
        if ($this->real === 0.0 && abs($this->imaginary) === 1.0) {
            $value = $this->imaginary > 0 ? 'i' : '-i';
            throw new ArithmeticException("Cannot compute inverse tangent of $value.");
        }

        // atan(x + iy) is atanh(y + ix) with its parts swapped.
        [$real, $imag] = self::calcAtanh($this->imaginary, $this->real);
        return new self($imag, $real);
    }

    #endregion

    #region Hyperbolic methods

    /**
     * Calculate the hyperbolic sine of this complex number.
     *
     * @return self A new complex number representing the hyperbolic sine of this complex number.
     * @throws OverflowException If the result is too large to represent as a float.
     * @see https://en.wikipedia.org/wiki/Hyperbolic_functions#Complex_arguments
     */
    public function sinh(): self
    {
        // sinh(z) = sinh(x)cos(y) + i·cosh(x)sin(y)
        // where z = x + iy
        $x = $this->real;
        $y = $this->imaginary;
        return self::fromResult(sinh($x) * cos($y), cosh($x) * sin($y), 'compute hyperbolic sine of the given Complex');
    }

    /**
     * Calculate the hyperbolic cosine of this complex number.
     *
     * @return self A new complex number representing the hyperbolic cosine of this complex number.
     * @throws OverflowException If the result is too large to represent as a float.
     * @see https://en.wikipedia.org/wiki/Hyperbolic_functions#Complex_arguments
     */
    public function cosh(): self
    {
        // cosh(z) = cosh(x)cos(y) + i·sinh(x)sin(y)
        // where z = x + iy
        $x = $this->real;
        $y = $this->imaginary;
        return self::fromResult(
            cosh($x) * cos($y),
            sinh($x) * sin($y),
            'compute hyperbolic cosine of the given Complex'
        );
    }

    /**
     * Calculate the hyperbolic tangent of this complex number.
     *
     * Uses Kahan's algorithm, so it never overflows. For a large real part, the result is about ±1.
     *
     * @return self A new complex number representing the hyperbolic tangent of this complex number.
     * @see https://en.wikipedia.org/wiki/Hyperbolic_functions#Complex_arguments
     */
    public function tanh(): self
    {
        [$real, $imag] = self::calcTanh($this->real, $this->imaginary);
        return new self($real, $imag);
    }

    #endregion

    #region Inverse hyperbolic methods

    /**
     * Calculate the inverse hyperbolic sine of this complex number.
     *
     * The result is the principal value, accurate to within a few ulps for any argument. On a branch cut (the
     * imaginary axis outside [-i, i]), the sign of the real part, including a signed zero, chooses the side, as in C99.
     *
     * @return self A new complex number representing the inverse hyperbolic sine of this complex number.
     * @see https://en.wikipedia.org/wiki/Inverse_hyperbolic_functions#Complex_arguments
     */
    public function asinh(): self
    {
        [$real, $imag] = self::calcAsinh($this->real, $this->imaginary);
        return new self($real, $imag);
    }

    /**
     * Calculate the inverse hyperbolic cosine of this complex number.
     *
     * The result is the principal value, whose real part is never negative, accurate to within a few ulps for any
     * argument. On the branch cut (the real axis below 1), the sign of the imaginary part, including a signed zero,
     * chooses the side, as in C99.
     *
     * @return self A new complex number representing the inverse hyperbolic cosine of this complex number.
     * @see https://en.wikipedia.org/wiki/Inverse_hyperbolic_functions#Complex_arguments
     */
    public function acosh(): self
    {
        // acosh(z) is ±i·acos(z), with the sign chosen so the real part isn't negative.
        [$acosReal, $acosImag] = self::calcAcos($this->real, $this->imaginary);
        return new self(abs($acosImag), Numbers::copySign($acosReal, $this->imaginary));
    }

    /**
     * Calculate the inverse hyperbolic tangent of this complex number.
     *
     * The result is the principal value, accurate to within a few ulps for any argument. On a branch cut (the real
     * axis outside [-1, 1]), the sign of the imaginary part, including a signed zero, chooses the side, as in C99.
     *
     * @return self A new complex number representing the inverse hyperbolic tangent of this complex number.
     * @throws ArithmeticException If this Complex is 1 or -1, where the inverse hyperbolic tangent is undefined.
     * @see https://en.wikipedia.org/wiki/Inverse_hyperbolic_functions#Complex_arguments
     */
    public function atanh(): self
    {
        // atanh(z) is undefined at z = ±1.
        if ($this->imaginary === 0.0 && abs($this->real) === 1.0) {
            $value = $this->real > 0 ? '1' : '-1';
            throw new ArithmeticException("Cannot compute inverse hyperbolic tangent of $value.");
        }

        [$real, $imag] = self::calcAtanh($this->real, $this->imaginary);
        return new self($real, $imag);
    }

    #endregion

    #region Rounding methods

    /**
     * Round the real and imaginary parts to the given number of decimal places.
     *
     * If $precision is positive, the Complex parts are rounded to $precision digits after the decimal point.
     * If $precision is negative, the Complex parts are rounded to the nearest multiple of pow(10, -$precision), e.g. to
     * tens for a precision of -1, to hundreds for a precision of -2, etc.
     *
     * @param int $precision The number of decimal places to round to.
     * @param RoundingMode $mode The rounding mode to use. Defaults to HalfAwayFromZero, matching the default mode
     * used by PHP's own round() function.
     * @return self A new complex number with both parts rounded.
     * @see https://www.php.net/manual/en/function.round.php
     */
    public function round(int $precision = 0, RoundingMode $mode = RoundingMode::HalfAwayFromZero): self
    {
        return new self(round($this->real, $precision, $mode), round($this->imaginary, $precision, $mode));
    }

    #endregion

    #region ArrayAccess implementation

    /**
     * Check if the complex number has a given offset. Only 0 and 1 are valid offsets.
     *
     * @param mixed $offset The offset to check.
     * @return bool True if the offset exists, false otherwise.
     */
    #[Override] // ArrayAccess
    public function offsetExists(mixed $offset): bool
    {
        return $offset === 0 || $offset === 1;
    }

    /**
     * Get the value of the complex number at the given offset. Only 0 and 1 are valid offsets.
     *
     * @param mixed $offset The offset to retrieve.
     * @return float The value at the given offset.
     * @throws InvalidArgumentException If the offset is not an int.
     * @throws OutOfRangeException If the offset is not 0 or 1.
     */
    #[Override] // ArrayAccess
    public function offsetGet(mixed $offset): float
    {
        // Check offset type.
        if (!is_int($offset)) {
            throw new InvalidArgumentException('Invalid offset type: ' . get_debug_type($offset) . '. Must be int.');
        }

        // Check offset value.
        if (!$this->offsetExists($offset)) {
            throw new OutOfRangeException("Invalid offset: $offset. Must be 0 or 1.");
        }

        // Return the appropriate value.
        return $offset === 0 ? $this->real : $this->imaginary;
    }

    /**
     * This method is unsupported because this class is immutable.
     *
     * @param mixed $offset The offset to set.
     * @param mixed $value The value to set.
     * @return void
     * @throws LogicException If called.
     */
    #[Override] // ArrayAccess
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('Complex values are immutable.');
    }

    /**
     * This method is unsupported because this class is immutable.
     *
     * @param mixed $offset The offset to unset.
     * @return void
     * @throws LogicException If called.
     */
    #[Override] // ArrayAccess
    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Complex values are immutable.');
    }

    #endregion

    #region Helper methods

    /**
     * Calculate a power of this complex number, for pow(), once the exponent 0 and 1 and zero base cases are handled.
     *
     * @param self $exp The exponent.
     * @return self The result.
     * @throws OverflowException If the result, or an intermediate value, is too large to represent as a float.
     */
    private function calcPow(self $exp): self
    {
        // Handle exponent = 2.
        if ($exp->equal(2)) {
            return $this->mul($this);
        }

        // Handle exponent = 0.5. Delegate to sqrt(), which is more accurate.
        if ($exp->equal(0.5)) {
            return $this->sqrt();
        }

        // Handle exponent = -1. Delegate to recip().
        if ($exp->equal(-1)) {
            return $this->recip();
        }

        // Handle base = e. This saves unnecessary calls to ln() and mul().
        if ($this->equal(M_E)) {
            return $exp->exp();
        }

        // For integer exponents, do exponentiation by squaring.
        if ($exp->isReal() && Floats::isInt($exp->real)) {
            $result = new self(1);
            $x = clone $this;
            // For negative exponents, invert the base. This works better than inverting the result at the end, because
            // it avoids a possible overflow for large negative exponents.
            if ($exp->real < 0) {
                $x = $x->recip();
            }
            $y = abs($exp->real);
            while ($y > 0) {
                if (fmod($y, 2) === 1.0) {
                    $result = $result->mul($x);
                }
                // Halve the exponent.
                $y = floor($y / 2);
                if ($y > 0) {
                    // Square the base.
                    $x = $x->mul($x);
                }
            }
            return $result;
        }

        // General solution. Calculate z^w = e^(w * ln(z)).
        return $exp->mul($this->ln())->exp();
    }

    /**
     * Calculate the hyperbolic tangent of x + iy, for tanh() and tan().
     *
     * Uses Kahan's algorithm, which is accurate and can't overflow, unlike sinh(z) / cosh(z).
     *
     * @param float $x The real part.
     * @param float $y The imaginary part.
     * @return array{float, float} The real and imaginary parts of the result.
     * @see https://people.freebsd.org/~das/kahan86branch.pdf W. Kahan, "Branch Cuts for Complex Elementary Functions".
     */
    private static function calcTanh(float $x, float $y): array
    {
        // For a large real part, tanh(z) = ±1 + i·4·sin(y)·cos(y)·e^(-2|x|), to within rounding. Multiplying by
        // e^(-|x|) twice lets the imaginary part underflow gradually, instead of e^(-2|x|) underflowing to 0 first.
        if (abs($x) >= 22) {
            $expMinusX = exp(-abs($x));
            $real = $x > 0 ? 1.0 : -1.0;
            $imag = 4 * sin($y) * cos($y) * $expMinusX * $expMinusX;
            return [$real, $imag];
        }

        // Otherwise, with t = tan(y), β = 1 + t², s = sinh(x) and ρ = √(1 + s²) = cosh(x):
        // tanh(z) = (β·ρ·s + i·t) / (1 + β·s²).
        $t = tan($y);
        $beta = 1 + $t * $t;
        $s = sinh($x);
        $rho = sqrt(1 + $s * $s);
        $denom = 1 + $beta * $s * $s;
        $real = $beta * $rho * $s / $denom;
        $imag = $t / $denom;
        return [$real, $imag];
    }

    /*
     * The inverse trigonometric and hyperbolic helpers below, calcAsinh() to calcLogLarge(), are ported from FreeBSD's
     * lib/msun/src/catrig.c, which implements the algorithms in T. E. Hull, T. F. Fairgrieve and P. T. P. Tang,
     * "Implementing the complex arcsine and arccosine functions using exception handling", ACM Transactions on
     * Mathematical Software 23(3), 1997, pages 299-335. The inputs are always finite here, so catrig.c's handling of
     * INF and NAN is left out. Its licence is reproduced here, as it requires:
     *
     * Copyright (c) 2012 Stephen Montgomery-Smith <stephen@FreeBSD.ORG>
     * All rights reserved.
     *
     * Redistribution and use in source and binary forms, with or without
     * modification, are permitted provided that the following conditions
     * are met:
     * 1. Redistributions of source code must retain the above copyright
     *    notice, this list of conditions and the following disclaimer.
     * 2. Redistributions in binary form must reproduce the above copyright
     *    notice, this list of conditions and the following disclaimer in the
     *    documentation and/or other materials provided with the distribution.
     *
     * THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS'' AND
     * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
     * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
     * ARE DISCLAIMED.  IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE
     * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
     * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
     * OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
     * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
     * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
     * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
     * SUCH DAMAGE.
     */

    /**
     * Calculate the inverse hyperbolic sine of x + iy, for asinh() and asin().
     *
     * With A = (|z + i| + |z - i|) / 2 and B = y / A, asinh(z) = sign(x)·ln(A + √(A² - 1)) + i·asin(B). See
     * calcAsinhParts() for how that's kept accurate.
     *
     * @param float $x The real part.
     * @param float $y The imaginary part.
     * @return array{float, float} The real and imaginary parts of the result.
     */
    private static function calcAsinh(float $x, float $y): array
    {
        $ax = abs($x);
        $ay = abs($y);

        // For a large argument, asinh(z) = sign(x)·(ln(sign(x)·z) + ln 2), to within rounding.
        if ($ax > self::RECIP_EPSILON || $ay > self::RECIP_EPSILON) {
            [$logReal, $logImag] = Floats::isNegative($x) ? self::calcLogLarge(-$x, -$y) : self::calcLogLarge($x, $y);
            return [
                Numbers::copySign($logReal + M_LN2, $x),
                Numbers::copySign($logImag, $y),
            ];
        }

        // For a small argument (including zero), asinh(z) = z, to within rounding.
        if ($ax < self::SQRT_6_EPSILON / 4 && $ay < self::SQRT_6_EPSILON / 4) {
            return [$x, $y];
        }

        [$rx, $bIsUsable, $b, $sqrtA2My2, $newY] = self::calcAsinhParts($ax, $ay);
        $ry = $bIsUsable ? asin($b) : atan2($newY, $sqrtA2My2);
        return [
            Numbers::copySign($rx, $x),
            Numbers::copySign($ry, $y),
        ];
    }

    /**
     * Calculate the inverse cosine of x + iy, for acos() and acosh().
     *
     * acos(z) = π/2 - asin(z), but calculated so it's accurate when z is close to 1.
     *
     * @param float $x The real part.
     * @param float $y The imaginary part.
     * @return array{float, float} The real and imaginary parts of the result.
     */
    private static function calcAcos(float $x, float $y): array
    {
        $ax = abs($x);
        $ay = abs($y);

        // For a large argument, acos(z) = -sign(y)·i·(ln(z) + ln 2), to within rounding.
        if ($ax > self::RECIP_EPSILON || $ay > self::RECIP_EPSILON) {
            [$logReal, $logImag] = self::calcLogLarge($x, $y);
            $ry = $logReal + M_LN2;
            return [
                abs($logImag),
                Floats::isNegative($y) ? $ry : -$ry,
            ];
        }

        // acos(1) = 0 exactly.
        if ($x === 1.0 && $y === 0.0) {
            return [0.0, -$y];
        }

        // For a small argument, acos(z) = π/2 - z, to within rounding.
        if ($ax < self::SQRT_6_EPSILON / 4 && $ay < self::SQRT_6_EPSILON / 4) {
            return [
                M_PI_2 - ($x - self::PI_2_LO),
                -$y,
            ];
        }

        // The same calculation as asinh(), with the parts swapped.
        [$ry, $bIsUsable, $b, $sqrtA2Mx2, $newX] = self::calcAsinhParts($ay, $ax);
        if ($bIsUsable) {
            $rx = acos(Floats::isNegative($x) ? -$b : $b);
        } else {
            $rx = atan2($sqrtA2Mx2, Floats::isNegative($x) ? -$newX : $newX);
        }
        return [
            $rx,
            Floats::isNegative($y) ? $ry : -$ry,
        ];
    }

    /**
     * Calculate the parts of asinh(x + iy) for calcAsinh() and calcAcos(), where x and y are non-negative and less than
     * RECIP_EPSILON. This is do_hard_work() in catrig.c.
     *
     * With A = (|z + i| + |z - i|) / 2 and B = y / A, the textbook formulas lose accuracy in two places:
     * - ln(A + √(A² - 1)), when z is close to the segment [-i, i]. Below A_CROSSOVER, it's calculated as
     *   log1p((A - 1) + √((A - 1)·(A + 1))), with A - 1 calculated without cancellation.
     * - asin(B), when z is close to the rays [i, i∞) or (-i∞, -i]. Above B_CROSSOVER, it's calculated as
     *   atan2(y, √(A² - y²)), with √(A² - y²) calculated without cancellation.
     *
     * @param float $x The real part. Must be non-negative.
     * @param float $y The imaginary part. Must be non-negative.
     * @return array{float, bool, float, float, float} The real part of asinh(z); whether B is usable; B; and if B
     * isn't usable, √(A² - y²) and y, both possibly scaled up by the same factor to avoid underflow.
     */
    private static function calcAsinhParts(float $x, float $y): array
    {
        // |z + i| and |z - i|.
        $r = hypot($x, $y + 1);
        $s = hypot($x, $y - 1);

        // A is at least 1 mathematically, but rounding could take it just below.
        $a = max(($r + $s) / 2, 1.0);

        // Calculate the real part, ln(A + √(A² - 1)).
        if ($a < self::A_CROSSOVER) {
            if ($y === 1.0 && $x < PHP_FLOAT_EPSILON * PHP_FLOAT_EPSILON / 128) {
                // A - 1 is about x / 2.
                $rx = sqrt($x);
            } elseif ($x >= PHP_FLOAT_EPSILON * abs($y - 1)) {
                $aMinus1 = self::calcHalfHypotDiff($x, 1 + $y, $r) + self::calcHalfHypotDiff($x, 1 - $y, $s);
                $rx = log1p($aMinus1 + sqrt($aMinus1 * ($a + 1)));
            } elseif ($y < 1) {
                // A is 1, to within rounding.
                $rx = $x / sqrt((1 - $y) * (1 + $y));
            } else {
                // A - 1 is y - 1, to within rounding.
                $rx = log1p(($y - 1) + sqrt(($y - 1) * ($y + 1)));
            }
        } else {
            $rx = log($a + sqrt($a * $a - 1));
        }

        // For a tiny y, y / A could underflow, so return √(A² - y²) and y, scaled up, for atan2().
        if ($y < self::FOUR_SQRT_MIN) {
            return [
                $rx,
                false,
                0.0,
                $a * (2 / PHP_FLOAT_EPSILON),
                $y * (2 / PHP_FLOAT_EPSILON),
            ];
        }

        // B = y / A, which is usable unless it's close to 1, where asin() loses accuracy.
        $b = $y / $a;
        if ($b <= self::B_CROSSOVER) {
            return [$rx, true, $b, 0.0, $y];
        }

        // Calculate √(A² - y²) for atan2().
        $newY = $y;
        if ($y === 1.0 && $x < PHP_FLOAT_EPSILON / 128) {
            // A - y is about x / 2.
            $sqrtA2My2 = sqrt($x) * sqrt(($a + $y) / 2);
        } elseif ($x >= PHP_FLOAT_EPSILON * abs($y - 1)) {
            $aMinusY = self::calcHalfHypotDiff($x, $y + 1, $r) + self::calcHalfHypotDiff($x, $y - 1, $s);
            $sqrtA2My2 = sqrt($aMinusY * ($a + $y));
        } elseif ($y > 1) {
            // A is y, to within rounding. Scale up to avoid underflow; y < RECIP_EPSILON, so this can't overflow.
            $scale = 4 / PHP_FLOAT_EPSILON / PHP_FLOAT_EPSILON;
            $sqrtA2My2 = $x * $scale * $y / sqrt(($y + 1) * ($y - 1));
            $newY = $y * $scale;
        } else {
            // A is 1, to within rounding.
            $sqrtA2My2 = sqrt((1 - $y) * (1 + $y));
        }

        return [$rx, false, $b, $sqrtA2My2, $newY];
    }

    /**
     * Calculate (hypot(a, b) - b) / 2 without cancellation. This is f() in catrig.c.
     *
     * @param float $a The first value.
     * @param float $b The second value.
     * @param float $hypot hypot(a, b), already calculated.
     * @return float (hypot(a, b) - b) / 2.
     */
    private static function calcHalfHypotDiff(float $a, float $b, float $hypot): float
    {
        if ($b < 0) {
            return ($hypot - $b) / 2;
        }
        if ($b === 0.0) {
            return $a / 2;
        }
        // hypot(a, b) - b = a² / (hypot(a, b) + b), which doesn't cancel when b is positive.
        return $a * $a / ($hypot + $b) / 2;
    }

    /**
     * Calculate the inverse hyperbolic tangent of x + iy, for atanh() and atan().
     *
     * atanh(z) = ln((1 + z) / (1 - z)) / 2 = log1p(4x / |z - 1|²) / 4 + i·atan2(2y, (1 - x)(1 + x) - y²) / 2.
     *
     * @param float $x The real part.
     * @param float $y The imaginary part. If y is 0, x must not be ±1.
     * @return array{float, float} The real and imaginary parts of the result.
     */
    private static function calcAtanh(float $x, float $y): array
    {
        $ax = abs($x);
        $ay = abs($y);

        // A real argument in [-1, 1].
        if ($y === 0.0 && $ax <= 1) {
            return [
                atanh($x),
                $y,
            ];
        }

        // An imaginary argument, matching the accuracy of atan().
        if ($x === 0.0) {
            return [
                $x,
                atan($y),
            ];
        }

        // For a large argument, atanh(z) = 1/z + sign(y)·iπ/2, to within rounding.
        if ($ax > self::RECIP_EPSILON || $ay > self::RECIP_EPSILON) {
            return [
                self::calcRealPartReciprocal($x, $y),
                Numbers::copySign(M_PI_2, $y),
            ];
        }

        // For a small argument, atanh(z) = z, to within rounding.
        if ($ax < self::SQRT_3_EPSILON / 2 && $ay < self::SQRT_3_EPSILON / 2) {
            return [$x, $y];
        }

        // Real part. Near ±1, log1p(4/|z - 1|²) is ln(4/y²) = ln 2 - 2·ln(y), which avoids squaring a tiny y.
        if ($ax === 1.0 && $ay < PHP_FLOAT_EPSILON) {
            $rx = (M_LN2 - log($ay)) / 2;
        } else {
            $ax1 = $ax - 1;
            $sumSquares = $ay < self::SQRT_MIN ? $ax1 * $ax1 : $ax1 * $ax1 + $ay * $ay;
            $rx = log1p(4 * $ax / $sumSquares) / 4;
        }

        // Imaginary part.
        if ($ax === 1.0) {
            $ry = atan2(2, -$ay) / 2;
        } elseif ($ay < PHP_FLOAT_EPSILON) {
            $ry = atan2(2 * $ay, (1 - $ax) * (1 + $ax)) / 2;
        } else {
            $ry = atan2(2 * $ay, (1 - $ax) * (1 + $ax) - $ay * $ay) / 2;
        }

        return [
            Numbers::copySign($rx, $x),
            Numbers::copySign($ry, $y),
        ];
    }

    /**
     * Calculate the real part of 1 / (x + iy), x / (x² + y²), for a large x + iy, without overflowing or underflowing
     * needlessly.
     *
     * @param float $x The real part.
     * @param float $y The imaginary part.
     * @return float The real part of the reciprocal.
     */
    private static function calcRealPartReciprocal(float $x, float $y): float
    {
        // Scale so the larger part is 1, so the squares can't overflow.
        $scale = max(abs($x), abs($y));
        $x /= $scale;
        $y /= $scale;
        return $x / ($x * $x + $y * $y) / $scale;
    }

    /**
     * Calculate ln(x + iy) for a large x + iy, without overflowing. This is clog_for_large_values() in catrig.c.
     *
     * @param float $x The real part.
     * @param float $y The imaginary part.
     * @return array{float, float} The real and imaginary parts of the logarithm.
     */
    private static function calcLogLarge(float $x, float $y): array
    {
        $absx = abs($x);
        $absy = abs($y);
        $max = max($absx, $absy);
        $min = min($absx, $absy);

        // hypot() could overflow, so divide both parts by e, and add 1 to the logarithm.
        if ($max > PHP_FLOAT_MAX / 2) {
            return [
                log(hypot($x / M_E, $y / M_E)) + 1,
                atan2($y, $x),
            ];
        }

        // Squaring could overflow or underflow.
        if ($max > self::QUARTER_SQRT_MAX || $min < self::SQRT_MIN) {
            return [
                log(hypot($x, $y)),
                atan2($y, $x),
            ];
        }

        return [
            log($max * $max + $min * $min) / 2,
            atan2($y, $x),
        ];
    }

    /**
     * Create a Complex from the parts of a calculated result, checking the result is representable.
     *
     * With finite operands, a non-finite part means the result overflowed. A NAN part means an intermediate value
     * overflowed (e.g. INF - INF or 0 × INF), so it's reported as an overflow too.
     *
     * @param float $real The real part.
     * @param float $imag The imaginary part.
     * @param string $operation The operation, for the exception message, e.g. 'add the given Complex numbers'.
     * @return self The new Complex.
     * @throws OverflowException If either part, or the magnitude, is not finite.
     */
    private static function fromResult(float $real, float $imag, string $operation): self
    {
        if (!is_finite($real) || !is_finite($imag)) {
            throw new OverflowException("Cannot $operation. The result would overflow.");
        }

        // The constructor throws if the magnitude overflows.
        return self::calcWithOverflowMessage($operation, static fn () => new self($real, $imag));
    }

    /**
     * Run a calculation, replacing the message of an OverflowException from any step with one that names the
     * operation the caller ran.
     *
     * @param string $operation The operation, for the exception message, e.g. 'compute tangent of the given Complex'.
     * @param callable(): self $calc The calculation.
     * @return self The result.
     * @throws OverflowException If the result, or an intermediate value, is too large to represent as a float.
     */
    private static function calcWithOverflowMessage(string $operation, callable $calc): self
    {
        try {
            return $calc();
        } catch (OverflowException $e) {
            throw new OverflowException("Cannot $operation. The result would overflow.", 0, $e);
        }
    }

    #endregion
}
