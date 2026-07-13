<?php

declare(strict_types=1);

namespace OceanMoon\Math;

use ArrayAccess;
use DivisionByZeroError;
use DomainException;
use JsonSerializable;
use LogicException;
use OceanMoon\Core\Exceptions\ConversionException;
use OceanMoon\Core\Floats;
use OceanMoon\Core\Numbers;
use OceanMoon\Core\Stringify;
use OceanMoon\Core\Traits\Comparison\ApproxEquatable;
use OutOfRangeException;
use Override;
use stdClass;
use Stringable;
use Throwable;

use const OceanMoon\Core\M_TAU;

/**
 * Encapsulates a complex number and provides a number of useful methods.
 *
 * @implements ArrayAccess<int, float>
 */
final class Complex implements Stringable, ArrayAccess, JsonSerializable
{
    use ApproxEquatable;

    #region Properties

    #region Public properties (readonly)

    /**
     * The real part of the complex number.
     *
     * @var float
     */
    private(set) float $real;

    /**
     * The imaginary part of the complex number.
     *
     * @var float
     */
    private(set) float $imaginary;

    #endregion

    #region Computed properties (public, readonly)

    /**
     * The magnitude (a.k.a. absolute value or modulus) of this complex number.
     *
     * @var null|float
     */
    private(set) ?float $magnitude = null {
        get {
            // Compute and cache if necessary.
            if ($this->magnitude === null) {
                $this->magnitude = $this->isReal() ? abs($this->real) : hypot($this->real, $this->imaginary);
            }

            return $this->magnitude;
        }
    }

    /**
     * The phase (a.k.a. argument) of this complex number in radians.
     *
     * It is stored in a canonical form, in the range (-π, π]. This is known as the principal value.
     * @see https://en.wikipedia.org/wiki/Principal_value#Complex_argument
     *
     * @var null|float
     */
    private(set) ?float $phase = null {
        get {
            // Compute and cache if necessary.
            if ($this->phase === null) {
                if ($this->isReal()) {
                    $this->phase = $this->real < 0 ? M_PI : 0;
                } else {
                    // atan2() can return a value in the range [-π, π] inclusive, which isn't canonical.
                    // The call to wrap() will convert the value to a range of (-π to π]
                    $this->phase = Floats::wrap(atan2($this->imaginary, $this->real));
                }
            }

            return $this->phase;
        }
    }

    #endregion

    #endregion

    #region Constructor

    /**
     * Create a new complex number.
     *
     * @param float $real The real part.
     * @param float $imag The imaginary part.
     * @throws DomainException If either part is not finite (±INF or NAN).
     */
    public function __construct(float $real = 0, float $imag = 0)
    {
        // Check for non-finite values.
        if (!is_finite($real)) {
            throw new DomainException('Cannot create Complex. Real part must be finite.');
        }
        if (!is_finite($imag)) {
            throw new DomainException('Cannot create Complex. Imaginary part must be finite.');
        }

        // Set the properties.
        $this->real = $real;
        $this->imaginary = $imag;
    }

    #endregion

    #region Factory methods

    /**
     * Validate a candidate real/imaginary pair and construct a Complex from them.
     *
     * Shared by fromArray(), fromObject(), and fromString(). Each extracts $real and $imag from its own input
     * format, then delegates here for the validation and construction common to all three, so that a non-numeric or
     * non-finite value is reported as a ConversionException referencing the original input, rather than letting the
     * constructor's own DomainException leak through unwrapped.
     *
     * @param mixed $orig The original value being converted (for the exception message only).
     * @param mixed $real The candidate real part.
     * @param mixed $imag The candidate imaginary part.
     * @return self The equivalent Complex.
     * @throws ConversionException If $real or $imag is not a finite number.
     */
    private static function make(mixed $orig, mixed $real, mixed $imag): self
    {
        // Check values are numbers.
        if (!Numbers::isNumber($real)) {
            throw new ConversionException($orig, self::class, 'Real part must be a number (int or float).');
        }
        if (!Numbers::isNumber($imag)) {
            throw new ConversionException($orig, self::class, 'Imaginary part must be a number (int or float).');
        }

        try {
            return new self($real, $imag);
        } catch (Throwable $e) {
            ConversionException::rethrow($orig, self::class, $e);
        }
    }

    /**
     * Create a Complex from an array.
     *
     * The array must contain two numeric elements that will be interpreted as the real and imaginary parts.
     *
     * The method supports two kinds of arrays: list and associative:
     * 1. If list, there must be exactly two elements, and they will be interpreted as [real, imaginary].
     * The order of the values matters. This version will support the result of a call to toArray().
     * 2. If associative, the array must have keys 'real' and 'imaginary', which must both be numeric. The order of the
     * values doesn't matter. This version will support the result of (array) $complex, which includes keys for
     * 'magnitude' and 'phase', but these will be ignored.
     *
     * So: both [3, 4] and ['real' => 3, 'imaginary' => 4, ...] will be converted to 3 + 4i.
     *
     * @param array<array-key, mixed> $arr The array to convert.
     * @return self The equivalent Complex.
     * @throws ConversionException If the array could not be converted to a Complex.
     */
    public static function fromArray(array $arr): self
    {
        // Handle empty input.
        if (empty($arr)) {
            throw new ConversionException($arr, self::class, 'Array cannot be empty.');
        }

        if (array_is_list($arr)) {
            // List array: [real, imaginary]. Check for exactly two elements.
            if (count($arr) !== 2) {
                throw new ConversionException($arr, self::class, 'List array must contain exactly two elements.');
            }

            $real = $arr[0];
            $imag = $arr[1];
        } else {
            // Associative array: ['real' => ..., 'imaginary' => ...]. Check for properties.
            if (!array_key_exists('real', $arr)) {
                throw new ConversionException($arr, self::class, 'Associative array must include key "real".');
            }
            if (!array_key_exists('imaginary', $arr)) {
                throw new ConversionException($arr, self::class, 'Associative array must include key "imaginary".');
            }

            $real = $arr['real'];
            $imag = $arr['imaginary'];
        }

        return self::make($arr, $real, $imag);
    }

    /**
     * Create a Complex from an object with "real" and "imaginary" properties.
     *
     * @param object $obj The object to convert.
     * @return self The equivalent Complex.
     * @throws ConversionException If the object could not be converted to a Complex.
     */
    public static function fromObject(object $obj): self
    {
        // Check properties exist.
        if (!property_exists($obj, 'real')) {
            throw new ConversionException($obj, self::class, 'Object must have property "real".');
        }
        if (!property_exists($obj, 'imaginary')) {
            throw new ConversionException($obj, self::class, 'Object must have property "imaginary".');
        }

        return self::make($obj, $obj->real, $obj->imaginary);
    }

    /**
     * Create a Complex from a string.
     *
     * Supports various formats:
     * - Real numbers: "5", "-3.14", "0"
     * - Pure imaginary: "i", "-i", "3i", "-2.5j", "I", "J"
     * - Complex: "3+4i", "5-2j", "-1+i", "2.5-3.7I"
     * - Spaces allowed: "3 + 4i", "5 - 2j"
     * - Either order: "4i+3", "-2j+5"
     *
     * @param string $str The string to convert.
     * @return self The equivalent Complex.
     * @throws ConversionException If the string could not be converted to a Complex.
     */
    public static function fromString(string $str): self
    {
        // Trim whitespace.
        $str = trim($str);

        // Handle empty string
        if ($str === '') {
            throw new ConversionException($str, self::class, 'String must not be empty.');
        }

        $rxNum = '(?:\d+\.?\d*|\.\d+)(?:[eE][+-]?\d+)?';
        if (preg_match("/^([+-]?)($rxNum)$/", $str, $matches)) {
            // Pattern: ±a (real only)
            [, $realSign, $realVal] = $matches;
            $imagSign = '';
            $imagVal = 0;
        } elseif (preg_match("/^([+-]?)((?:$rxNum)?)[ijIJ]$/", $str, $matches)) {
            // Pattern: ±bi (imaginary only)
            [, $imagSign, $imagVal] = $matches;
            $realSign = '';
            $realVal = 0;
        } elseif (preg_match("/^([+-]?)($rxNum)\s*([+-])\s*((?:$rxNum)?)[ijIJ]\$/", $str, $matches)) {
            // Pattern: ±a ± bi (real + imag)
            [, $realSign, $realVal, $imagSign, $imagVal] = $matches;
        } elseif (preg_match("/^([+-]?)((?:$rxNum)?)[ijIJ]\s*([+-])\s*($rxNum)\$/", $str, $matches)) {
            // Pattern: ±bi ± a (imag + real)
            [, $imagSign, $imagVal, $realSign, $realVal] = $matches;
        } else {
            throw new ConversionException($str, self::class, 'Invalid format.');
        }

        // Get the real part.
        $real = ($realSign === '-' ? -1 : 1) * (float) $realVal;

        // Get the imaginary part. Handle cases where the imaginary coefficient is omitted (like +i or -i).
        $imag = ($imagSign === '-' ? -1 : 1) * ($imagVal === '' ? 1.0 : (float) $imagVal);

        return self::make($str, $real, $imag);
    }

    /**
     * Create a complex number from polar coordinates.
     *
     * @param float $mag The magnitude (distance from origin).
     * @param float $phase The phase angle in radians.
     * @return self The equivalent Complex.
     * @throws DomainException If the magnitude is not positive.
     */
    public static function fromPolar(float $mag, float $phase): self
    {
        // Check for non-finite values.
        if (!is_finite($mag)) {
            throw new DomainException('Cannot create Complex. Magnitude must be finite.');
        }
        if (!is_finite($phase)) {
            throw new DomainException('Cannot create Complex. Phase must be finite.');
        }

        // Check for valid magnitude.
        if ($mag < 0) {
            throw new DomainException("Cannot create Complex. Magnitude must not be negative: $mag.");
        }

        // Get the phase as radians in the normal range (-pi, pi]
        $phase = Floats::wrap($phase);

        // Construct the new Complex.
        $z = new self($mag * cos($phase), $mag * sin($phase));

        // Remember the magnitude and phase since we know them.
        $z->magnitude = $mag;
        $z->phase = $phase;

        return $z;
    }

    /**
     * Convert the input value to a Complex, if not already, and if possible.
     *
     * NB:
     * - An array can be converted to a Complex only if it contains exactly two numeric elements, which are interpreted
     *   as the real and imaginary parts.
     * - An object can be converted to a Complex only if it has public properties named "real" and "imaginary", which
     *   are numeric.
     *
     * @param mixed $value The value to convert.
     * @return self The equivalent Complex.
     * @throws ConversionException If the value could not be converted to a Complex.
     */
    public static function toComplex(mixed $value): self
    {
        // Check for Complex.
        if ($value instanceof self) {
            return $value;
        }

        // Check for number (int or float).
        if (Numbers::isNumber($value)) {
            try {
                return new self($value);
            } catch (DomainException) {
                throw new ConversionException($value, self::class, 'Value must be a finite number.');
            }
        }

        // Check for string and convert to Complex if possible.
        if (is_string($value)) {
            return self::fromString($value);
        }

        // Check for array and convert to Complex if possible.
        if (is_array($value)) {
            return self::fromArray($value);
        }

        // Check for object and convert to Complex if possible.
        if (is_object($value)) {
            return self::fromObject($value);
        }

        // The value has a type that cannot be converted to Complex.
        throw new ConversionException($value, self::class);
    }

    #endregion

    #region Conversion methods

    /**
     * Convert the complex number to a plain object.
     *
     * @return stdClass An object with 'real' and 'imaginary' properties.
     */
    public function toObject(): stdClass
    {
        return (object) $this->__serialize();
    }

    /**
     * Convert the complex number to an array.
     *
     * @return array{0: float, 1: float} An array containing the real and imaginary parts of the
     * complex number.
     */
    public function toArray(): array
    {
        return [$this->real, $this->imaginary];
    }

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
     * identical() is inherited from the Equatable trait (via ApproxEquatable): true only if $other
     * is the same type (checked via Types::same(), not instanceof, so a subclass wouldn't count --
     * though Complex is final, so that distinction is moot here) and equal() to it. Since equal()
     * below already does an exact real/imaginary comparison as its baseline case (before any
     * broader type conversion via toComplex()), the trait default is exactly equivalent to a
     * hand-written version and needs no override.
     */

    /**
     * Check if this Complex is equal to another value, which may be Complex, int, float, string, array, or object;
     * i.e. anything that can be accepted by toComplex().
     *
     * @param mixed $other The value to compare with.
     * @return bool True if the numbers are equal.
     * @throws ConversionException If the value cannot be converted to a Complex.
     */
    /** @disregard P1128 */
    #[Override] // Equatable
    public function equal(mixed $other): bool
    {
        // Get other value as a Complex.
        $other = self::toComplex($other);

        // Check the real and imaginary parts are exactly equal.
        return $this->real === $other->real && $this->imaginary === $other->imaginary;
    }

    /**
     * Check if this complex number is approximately equal to another value, which may be Complex, int, float, string,
     * array, or object; i.e. anything that can be accepted by toComplex().
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
     * @throws ConversionException If the value cannot be converted to a Complex.
     */
    #[Override] // ApproxEquatable
    public function approxEqual(
        mixed $other,
        float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
        float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
    ): bool {
        // Get other value as a Complex.
        $other = self::toComplex($other);

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
     */
    public function inv(): self
    {
        return new self(1)->div($this);
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
     * @throws ConversionException If $other is a non-finite float (±INF or NAN).
     */
    public function add(self|float $other): self
    {
        // Get other value as a Complex.
        $other = self::toComplex($other);

        // Do the addition.
        return new self($this->real + $other->real, $this->imaginary + $other->imaginary);
    }

    /**
     * Subtract another complex number from this one.
     *
     * @param self|float $other The real or complex number to subtract.
     * @return self A new complex number representing the difference.
     * @throws ConversionException If $other is a non-finite float (±INF or NAN).
     */
    public function sub(self|float $other): self
    {
        // Get other value as a Complex.
        $other = self::toComplex($other);

        // Do the subtraction.
        return new self($this->real - $other->real, $this->imaginary - $other->imaginary);
    }

    /**
     * Multiply this complex number by another.
     * Uses the formula: (a + bi)(c + di) = (ac - bd) + (ad + bc)i
     *
     * @param self|float $other The real or complex number to multiply by.
     * @return self A new complex number representing the product.
     * @throws ConversionException If $other is a non-finite float (±INF or NAN).
     */
    public function mul(self|float $other): self
    {
        // Get other value as a Complex.
        $other = self::toComplex($other);

        // Do the multiplication.
        $a = $this->real;
        $b = $this->imaginary;
        $c = $other->real;
        $d = $other->imaginary;
        return new self($a * $c - $b * $d, $a * $d + $b * $c);
    }

    /**
     * Divide this complex number by another.
     * Uses the formula: (a + bi)/(c + di) = [(ac + bd) + (bc - ad)i]/(c² + d²)
     *
     * @param self|float $other The real or complex number to divide by.
     * @return self A new complex number representing the quotient.
     * @throws ConversionException If $other is a non-finite float (±INF or NAN).
     * @throws DivisionByZeroError If the divisor is zero.
     */
    public function div(self|float $other): self
    {
        // Get other value as a Complex.
        $other = self::toComplex($other);

        // Check for division by zero.
        if ($other->equal(0)) {
            throw new DivisionByZeroError('Cannot divide by zero.');
        }

        // Do the division.
        $a = $this->real;
        $b = $this->imaginary;
        $c = $other->real;
        $d = $other->imaginary;
        $f = ($c * $c) + ($d * $d);
        return new self(($a * $c + $b * $d) / $f, ($b * $c - $a * $d) / $f);
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
     * Multivalued cases:
     * - Complex base with fractional exponent: z^(1/n)
     * - Negative real base with fractional exponent: (-2)^(1/3)
     * - Any base with complex exponent: z^(a+bi) where b ≠ 0
     *
     * @param self|float $other The real or complex number to raise this complex number to.
     * @return self A new complex number representing the result.
     * @throws ConversionException If $other is a non-finite float (±INF or NAN).
     * @throws DomainException If attempting 0 raised to a negative or complex power.
     */
    public function pow(self|float $other): self
    {
        // Get other value as a Complex.
        $other = self::toComplex($other);

        // Handle exponent = 0. Any number to power 0 is 1.
        // Although mathematically 0^0 is undefined, we return 1 for consistency with pow(0, 0).
        // This is a common result in many programming languages and hence follows the Principle of Least Astonishment.
        // @see https://en.wikipedia.org/wiki/Zero_to_the_power_of_zero
        if ($other->equal(0)) {
            return new self(1);
        }

        // Handle base = 0.
        if ($this->equal(0)) {
            // Check for complex exponent.
            if (!$other->isReal()) {
                throw new DomainException('Cannot raise zero to a complex power.');
            }

            // Check for negative real exponent.
            if ($other->real < 0) {
                throw new DomainException('Cannot raise zero to a negative power.');
            }

            // The exponent is a positive real number. 0 raised to any positive real number is 0.
            return new self();
        }

        // Handle exponent = 1. Any number to power 1 is itself.
        if ($other->equal(1)) {
            return $this;
        }

        // Handle exponent = 2. Delegate to sqr().
        if ($other->equal(2)) {
            return $this->sqr();
        }

        // Handle exponent = -1. Delegate to inv().
        if ($other->equal(-1)) {
            return $this->inv();
        }

        // Handle base = e. This saves unnecessary calls to ln() and mul().
        if ($this->equal(M_E)) {
            return $other->exp();
        }

        // General solution. Calculate z^w = e^(w * ln(z)).
        return $other->mul($this->ln())->exp();
    }

    /**
     * Calculate the nth roots of this complex number.
     * Returns all n complex roots using De Moivre's theorem.
     *
     * @param int $n The root to calculate (e.g. 2 for square root, 3 for cube root).
     * @return list<self> An array of Complex numbers representing all nth roots.
     * @throws DomainException If n is not a positive integer.
     */
    public function roots(int $n): array
    {
        // Check for negative number of roots.
        if ($n <= 0) {
            throw new DomainException("Invalid root index: $n. Must be a positive integer.");
        }

        // Handle special case of 0.
        if ($this->equal(0)) {
            return [new self()];
        }

        // Calculate the magnitude of the roots.
        $rootMag = $this->magnitude ** (1.0 / $n);

        // Calculate all n roots.
        $roots = [];
        $theta = $this->phase / $n;
        $delta = M_TAU / $n;
        for ($k = 0; $k < $n; $k++) {
            $rootPhase = $theta + $k * $delta;
            $roots[] = self::fromPolar($rootMag, $rootPhase);
        }

        return $roots;
    }

    /**
     * Square this complex number.
     *
     * Equivalent to pow(2), but more efficient and readable.
     *
     * @return self A new complex number representing the square of this number.
     */
    public function sqr(): self
    {
        return $this->mul($this);
    }

    /**
     * Calculate the square root of this complex number.
     * Only the principal value is returned. For both square roots, call roots(2).
     *
     * @return self
     */
    public function sqrt(): self
    {
        assert(is_float($this->magnitude));
        assert(is_float($this->phase));
        return self::fromPolar(sqrt($this->magnitude), $this->phase / 2);
    }

    #endregion

    #region Transcendental methods

    /**
     * Calculate e^z where z is this complex number.
     *
     * @return self A new complex number representing e^z.
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

        // General solution. Uses Euler's formula: e^(a + bi) = e^a * (cos(b) + i*sin(b))
        return self::fromPolar(exp($this->real), $this->imaginary);
    }

    /**
     * Calculate the natural logarithm of a complex number.
     *
     * @return self A new complex number representing ln(z).
     * @throws DomainException If the complex number is 0.
     */
    public function ln(): self
    {
        // Check for ln(0), which is undefined.
        if ($this->equal(0)) {
            throw new DomainException('Cannot compute the logarithm of zero.');
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
        /** @var float $mag */
        $mag = $this->magnitude;
        /** @var float $phase */
        $phase = $this->phase;
        return new self(log($mag), $phase);
    }

    /**
     * Calculate the logarithm of a complex number with the given base.
     * Uses the change of base formula: log_b(z) = ln(z) / ln(b)
     *
     * @param self|float $base The base for the logarithm.
     * @return self A new complex number representing log_b(z).
     * @throws ConversionException If $base is a non-finite float (±INF or NAN).
     * @throws DomainException If the base is 0, 1, or if this number is 0.
     */
    public function log(self|float $base): self
    {
        // Get base as a Complex.
        $base = self::toComplex($base);

        // Check for invalid base values.
        if ($base->equal(0)) {
            throw new DomainException('Cannot compute logarithm with base zero.');
        }
        if ($base->equal(1)) {
            throw new DomainException('Cannot compute logarithm with base one.');
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

        // Use built-in log() function when arguments are real.
        if ($this->isReal() && $base->isReal()) {
            return new self(log($this->real, $base->real));
        }

        // General solution. Compute log_b(z) = ln(z) / ln(b)
        return $this->ln()->div($base->ln());
    }

    #endregion

    #region Trigonometric methods

    /**
     * Calculate the sine of this complex number.
     *
     * @return self A new complex number representing the sine of this complex number.
     * @see https://en.wikipedia.org/wiki/Trigonometric_functions#In_the_complex_plane
     */
    public function sin(): self
    {
        // sin(z) = sin(x)cosh(y) + i·cos(x)sinh(y)
        // where z = x + iy
        $x = $this->real;
        $y = $this->imaginary;
        return new self(sin($x) * cosh($y), cos($x) * sinh($y));
    }

    /**
     * Calculate the cosine of this complex number.
     *
     * @return self A new complex number representing the cosine of this complex number.
     * @see https://en.wikipedia.org/wiki/Trigonometric_functions#In_the_complex_plane
     */
    public function cos(): self
    {
        // cos(z) = cos(x)cosh(y) - i·sin(x)sinh(y)
        // where z = x + iy
        $x = $this->real;
        $y = $this->imaginary;
        return new self(cos($x) * cosh($y), -sin($x) * sinh($y));
    }

    /**
     * Calculate the tangent of this complex number.
     *
     * @return self A new complex number representing the tangent of this complex number.
     * @see https://en.wikipedia.org/wiki/Trigonometric_functions#In_the_complex_plane
     */
    public function tan(): self
    {
        // tan(z) = sin(z) / cos(z)
        return $this->sin()->div($this->cos());
    }

    /**
     * Calculate the secant of this complex number.
     *
     * @return self A new complex number representing the secant of this complex number.
     * @see https://en.wikipedia.org/wiki/Trigonometric_functions#In_the_complex_plane
     */
    public function sec(): self
    {
        // sec(z) = 1 / cos(z)
        return $this->cos()->inv();
    }

    /**
     * Calculate the cosecant of this complex number.
     *
     * @return self A new complex number representing the cosecant of this complex number.
     * @see https://en.wikipedia.org/wiki/Trigonometric_functions#In_the_complex_plane
     */
    public function csc(): self
    {
        // csc(z) = 1 / sin(z)
        return $this->sin()->inv();
    }

    /**
     * Calculate the cotangent of this complex number.
     *
     * @return self A new complex number representing the cotangent of this complex number.
     * @see https://en.wikipedia.org/wiki/Trigonometric_functions#In_the_complex_plane
     */
    public function cot(): self
    {
        // cot(z) = cos(z) / sin(z)
        return $this->cos()->div($this->sin());
    }

    #endregion

    #region Inverse trigonometric methods

    /**
     * Calculate the inverse sine of this complex number.
     *
     * @return self A new complex number representing the inverse sine of this complex number.
     * @see https://en.wikipedia.org/wiki/Inverse_trigonometric_functions#Extension_to_the_complex_plane
     */
    public function asin(): self
    {
        // asin(z) = -i·ln(iz + √(1-z²))
        // iz = -y + ix (multiply by i directly)
        $iz = new self(-$this->imaginary, $this->real);
        // 1 - z²
        $oneMinusZ2 = new self(1)->sub($this->sqr());
        // -i·ln(iz + √(1-z²))
        return $iz->add($oneMinusZ2->sqrt())->ln()->mul(new self(0, -1));
    }

    /**
     * Calculate the inverse cosine of this complex number.
     *
     * @return self A new complex number representing the inverse cosine of this complex number.
     * @see https://en.wikipedia.org/wiki/Inverse_trigonometric_functions#Extension_to_the_complex_plane
     */
    public function acos(): self
    {
        // acos(z) = -i·ln(z + i·√(1-z²))
        // 1 - z²
        $oneMinusZ2 = new self(1)->sub($this->sqr());
        // i·√(1-z²) - multiply √(1-z²) by i directly
        $sqrt = $oneMinusZ2->sqrt();
        $iSqrt = new self(-$sqrt->imaginary, $sqrt->real);
        // -i·ln(z + i·√(1-z²))
        return $this->add($iSqrt)->ln()->mul(new self(0, -1));
    }

    /**
     * Calculate the inverse tangent of this complex number.
     *
     * @return self A new complex number representing the inverse tangent of this complex number.
     * @see https://en.wikipedia.org/wiki/Inverse_trigonometric_functions#Extension_to_the_complex_plane
     */
    public function atan(): self
    {
        // atan(z) = (-i/2)·ln((i-z)/(i+z))
        // i - z = -x + (1-y)i (calculate directly)
        $iMinusZ = new self(-$this->real, 1 - $this->imaginary);
        // i + z = x + (1+y)i (calculate directly)
        $iPlusZ = new self($this->real, 1 + $this->imaginary);
        // (-i/2)·ln((i-z)/(i+z))
        return $iMinusZ->div($iPlusZ)->ln()->mul(new self(0, -0.5));
    }

    /**
     * Calculate the inverse secant of this complex number.
     *
     * @return self A new complex number representing the inverse secant of this complex number.
     * @see https://en.wikipedia.org/wiki/Inverse_trigonometric_functions#Extension_to_the_complex_plane
     */
    public function asec(): self
    {
        // asec(z) = acos(1/z)
        return $this->inv()->acos();
    }

    /**
     * Calculate the inverse cosecant of this complex number.
     *
     * @return self A new complex number representing the inverse cosecant of this complex number.
     * @see https://en.wikipedia.org/wiki/Inverse_trigonometric_functions#Extension_to_the_complex_plane
     */
    public function acsc(): self
    {
        // acsc(z) = asin(1/z)
        return $this->inv()->asin();
    }

    /**
     * Calculate the inverse cotangent of this complex number.
     *
     * @return self A new complex number representing the inverse cotangent of this complex number.
     * @see https://en.wikipedia.org/wiki/Inverse_trigonometric_functions#Extension_to_the_complex_plane
     */
    public function acot(): self
    {
        // acot(z) = atan(1/z)
        return $this->inv()->atan();
    }

    #endregion

    #region Hyperbolic methods

    /**
     * Calculate the hyperbolic sine of this complex number.
     *
     * @return self A new complex number representing the hyperbolic sine of this complex number.
     * @see https://en.wikipedia.org/wiki/Hyperbolic_functions#Complex_arguments
     */
    public function sinh(): self
    {
        // sinh(z) = sinh(x)cos(y) + i·cosh(x)sin(y)
        // where z = x + iy
        $x = $this->real;
        $y = $this->imaginary;
        return new self(sinh($x) * cos($y), cosh($x) * sin($y));
    }

    /**
     * Calculate the hyperbolic cosine of this complex number.
     *
     * @return self A new complex number representing the hyperbolic cosine of this complex number.
     * @see https://en.wikipedia.org/wiki/Hyperbolic_functions#Complex_arguments
     */
    public function cosh(): self
    {
        // cosh(z) = cosh(x)cos(y) + i·sinh(x)sin(y)
        // where z = x + iy
        $x = $this->real;
        $y = $this->imaginary;
        return new self(cosh($x) * cos($y), sinh($x) * sin($y));
    }

    /**
     * Calculate the hyperbolic tangent of this complex number.
     *
     * @return self A new complex number representing the hyperbolic tangent of this complex number.
     * @see https://en.wikipedia.org/wiki/Hyperbolic_functions#Complex_arguments
     */
    public function tanh(): self
    {
        // tanh(z) = sinh(z) / cosh(z)
        return $this->sinh()->div($this->cosh());
    }

    /**
     * Calculate the hyperbolic secant of this complex number.
     *
     * @return self A new complex number representing the hyperbolic secant of this complex number.
     * @see https://en.wikipedia.org/wiki/Hyperbolic_functions#Complex_arguments
     */
    public function sech(): self
    {
        // sech(z) = 1 / cosh(z)
        return $this->cosh()->inv();
    }

    /**
     * Calculate the hyperbolic cosecant of this complex number.
     *
     * @return self A new complex number representing the hyperbolic cosecant of this complex number.
     * @see https://en.wikipedia.org/wiki/Hyperbolic_functions#Complex_arguments
     */
    public function csch(): self
    {
        // csch(z) = 1 / sinh(z)
        return $this->sinh()->inv();
    }

    /**
     * Calculate the hyperbolic cotangent of this complex number.
     *
     * @return self A new complex number representing the hyperbolic cotangent of this complex number.
     * @see https://en.wikipedia.org/wiki/Hyperbolic_functions#Complex_arguments
     */
    public function coth(): self
    {
        // coth(z) = cosh(z) / sinh(z)
        return $this->cosh()->div($this->sinh());
    }

    #endregion

    #region Inverse hyperbolic methods

    /**
     * Calculate the inverse hyperbolic sine of this complex number.
     *
     * @return self A new complex number representing the inverse hyperbolic sine of this complex number.
     * @see https://en.wikipedia.org/wiki/Inverse_hyperbolic_functions#Complex_arguments
     */
    public function asinh(): self
    {
        // asinh(z) = ln(z + √(z² + 1))
        $z2Plus1 = $this->sqr()->add(new self(1));
        return $this->add($z2Plus1->sqrt())->ln();
    }

    /**
     * Calculate the inverse hyperbolic cosine of this complex number.
     *
     * @return self A new complex number representing the inverse hyperbolic cosine of this complex number.
     * @see https://en.wikipedia.org/wiki/Inverse_hyperbolic_functions#Complex_arguments
     */
    public function acosh(): self
    {
        // acosh(z) = ln(z + √(z² - 1))
        $z2Minus1 = $this->sqr()->sub(new self(1));
        return $this->add($z2Minus1->sqrt())->ln();
    }

    /**
     * Calculate the inverse hyperbolic tangent of this complex number.
     *
     * @return self A new complex number representing the inverse hyperbolic tangent of this complex number.
     * @see https://en.wikipedia.org/wiki/Inverse_hyperbolic_functions#Complex_arguments
     */
    public function atanh(): self
    {
        // atanh(z) = (1/2)·ln((1+z)/(1-z))
        $onePlusZ = new self(1)->add($this);
        $oneMinusZ = new self(1)->sub($this);
        return $onePlusZ->div($oneMinusZ)->ln()->mul(0.5);
    }

    /**
     * Calculate the inverse hyperbolic secant of this complex number.
     *
     * @return self A new complex number representing the inverse hyperbolic secant of this complex number.
     * @see https://en.wikipedia.org/wiki/Inverse_hyperbolic_functions#Complex_arguments
     */
    public function asech(): self
    {
        // asech(z) = acosh(1/z)
        return $this->inv()->acosh();
    }

    /**
     * Calculate the inverse hyperbolic cosecant of this complex number.
     *
     * @return self A new complex number representing the inverse hyperbolic cosecant of this complex number.
     * @see https://en.wikipedia.org/wiki/Inverse_hyperbolic_functions#Complex_arguments
     */
    public function acsch(): self
    {
        // acsch(z) = asinh(1/z)
        return $this->inv()->asinh();
    }

    /**
     * Calculate the inverse hyperbolic cotangent of this complex number.
     *
     * @return self A new complex number representing the inverse hyperbolic cotangent of this complex number.
     * @see https://en.wikipedia.org/wiki/Inverse_hyperbolic_functions#Complex_arguments
     */
    public function acoth(): self
    {
        // acoth(z) = atanh(1/z)
        return $this->inv()->atanh();
    }

    #endregion

    #region Serialization methods

    /**
     * Serialize the Complex object to an array.
     *
     * This method overrides the default serialization behavior, which includes the computed magnitude and phase
     * properties. However, those two properties aren't needed and shouldn't be included, as they may not be set, and
     * in any case are computed from the real and imaginary parts as needed.
     *
     * @return array{real: float, imaginary: float} An associative array containing the real and imaginary parts.
     */
    public function __serialize(): array
    {
        return [
            'real'      => $this->real,
            'imaginary' => $this->imaginary,
        ];
    }

    /**
     * Restore a Complex from serialized data, as produced by __serialize().
     *
     * Reconstructs via the constructor, so the usual finite-value validation applies to unserialized data just as it
     * does to normal construction. Without this method, PHP's default unserialize() behavior would assign "real" and
     * "imaginary" directly as properties, bypassing that validation entirely.
     *
     * Only "real" and "imaginary" are read from $data; any other keys (e.g. from a hand-crafted string) are ignored.
     * The $magnitude and $phase properties are unaffected — they retain their declared null default from object
     * allocation and are recomputed lazily on first access, same as after normal construction.
     *
     * @param array<string, mixed> $data The serialized data.
     * @throws DomainException If the data does not contain numeric "real" and "imaginary" values, or if either value is
     * not finite (±INF or NAN).
     */
    public function __unserialize(array $data): void
    {
        // Guard against missing values.
        if (!array_key_exists('real', $data) || !array_key_exists('imaginary', $data)) {
            throw new DomainException('Cannot unserialize Complex. Data must contain "real" and "imaginary" values.');
        }

        // Guard against non-numeric values.
        if (!Numbers::isNumber($data['real']) || !Numbers::isNumber($data['imaginary'])) {
            throw new DomainException(
                'Cannot unserialize Complex. Both "real" and "imaginary" values must be numeric (int or float).'
            );
        }

        // Call the constructor to validate and set the values.
        $this->__construct($data['real'], $data['imaginary']);
    }

    /**
     * Convert Complex to a value for JSON serialization.
     *
     * @return array{real: float, imaginary: float} An associative array containing the real and imaginary parts.
     */
    public function jsonSerialize(): array
    {
        return $this->__serialize();
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
     * @throws OutOfRangeException If the offset is invalid.
     */
    #[Override] // ArrayAccess
    public function offsetGet(mixed $offset): float
    {
        // Guard.
        if (!$this->offsetExists($offset)) {
            throw new OutOfRangeException('Invalid offset: ' . Stringify::abbrev($offset) . '.');
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
}
