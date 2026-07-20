# Complex

Immutable class representing complex numbers with comprehensive mathematical operations.

---

## Overview

The `Complex` class provides a complete implementation of complex number arithmetic with support for:

- Basic arithmetic (addition, subtraction, multiplication, division)
- Transcendental functions (exponential, logarithm, power, roots)
- Trigonometric and hyperbolic functions
- Conversion between rectangular (a + bi) and polar (r∠θ) forms
- Epsilon-based equality comparison for floating-point precision

All operations return new instances, maintaining immutability.

---

## Properties

### real

```php
private(set) float $real
```

The real part of the complex number. Read-only from outside the class.

### imaginary

```php
private(set) float $imaginary
```

The imaginary part of the complex number. Read-only from outside the class.

### magnitude

```php
private(set) ?float $magnitude
```

The magnitude (absolute value or modulus) of the complex number. Automatically computed and cached on first access.

For z = a + bi: |z| = √(a² + b²)

### phase

```php
private(set) ?float $phase
```

The phase (argument or angle) of the complex number in radians, normalized to the principal value range **(-π, π]**.
Automatically computed and cached on first access.

For z = a + bi: arg(z) = atan2(b, a), then wrapped to (-π, π]

The range excludes -π but includes π, following the standard mathematical convention for complex number arguments.

---

## Constructor

```php
public function __construct(float $real = 0, float $imag = 0)
```

Create a new complex number from real and imaginary parts.

**Parameters:**

- `$real` (float) - The real part (default: 0).
- `$imag` (float) - The imaginary part (default: 0).

**Examples:**

```php
$z1 = new Complex(3, 4);        // 3 + 4i
$z2 = new Complex(5);           // 5 + 0i (real number)
$z3 = new Complex(0, 2);        // 0 + 2i (pure imaginary)
$z4 = new Complex();            // 0 + 0i (zero)
```

**Note:** To create a complex number from a string, use the `fromString()` method.

**Throws:** `DomainException` if either part is not finite (±INF or NAN).

---

## Factory Methods

These are all static methods that return a `Complex`.

### fromString()

```php
public static function fromString(string $str): self
```

Create a Complex from a string. Supports various formats.

**Supported formats:**

- Real numbers: `"5"`, `"-3.14"`
- Pure imaginary: `"i"`, `"j"`, `"3i"`, `"-2.5j"`
- Complex (real first): `"3+4i"`, `"5-2j"`, `"-1+i"`
- Complex (imaginary first): `"4i+3"`, `"-2j+5"`, `"i-1"`
- Whitespace tolerant: `" 3 + 4i "`, `"5 - 2j"`
- Case insensitive: `"I"`, `"J"`

**Examples:**

```php
$z1 = Complex::fromString("3+4i");
$z2 = Complex::fromString("-2.5j");
$z3 = Complex::fromString("i");
$z4 = Complex::fromString("4i+3");
```

**Throws:** `FormatException` if the string is empty or does not match a supported format.

### fromPolar()

```php
public static function fromPolar(float $mag, float $phase): self
```

Create a complex number from polar coordinates (magnitude and phase).

**Parameters:**

- `$mag` (float) - The magnitude (r).
- `$phase` (float) - The phase angle in radians.

**Examples:**

```php
// Create from magnitude and phase
$z1 = Complex::fromPolar(5, M_PI / 4);
```

**Throws:** `DomainException` if the magnitude or phase is not finite (±INF or NAN), or if the magnitude is negative.

---

## Conversion Methods

### \_\_toString()

```php
public function __toString(): string
```

Convert to string representation.

**Format:**

- Real numbers: `"5"`
- Pure imaginary: `"i"`, `"3i"`, `"-2i"`
- Complex: `"3 + 4i"`, `"3 - 4i"`, `"-3 + 4i"`, `"-3 - 4i"`

**Examples:**

```php
echo new Complex(5);        // "5"
echo new Complex(0, 1);     // "i"
echo new Complex(0, -3);    // "-3i"
echo new Complex(3, 4);     // "3 + 4i"
echo new Complex(3, -4);    // "3 - 4i"
```

---

## Inspection Methods

### isReal()

```php
public function isReal(): bool
```

Check if the complex number is real (imaginary part is zero).

**Example:**

```php
$z1 = new Complex(5, 0);
var_dump($z1->isReal());  // true

$z2 = new Complex(3, 4);
var_dump($z2->isReal());  // false
```

---

## Comparison Methods

The `equal()` and `approxEqual()` methods are provided by the
[`ApproxEquatable`](https://github.com/mossy2100/PHP-Core/blob/main/docs/Traits/Comparison/ApproxEquatable.md) trait
from the [Core](https://github.com/mossy2100/PHP-Core) package, with `Complex` supplying its own type-checking logic
since the trait's parameter is typed `mixed` (see the trait's docs for why).

### equal()

```php
public function equal(mixed $other): bool
```

Check if this complex number exactly equals another value.

Compares both real and imaginary parts using exact equality (`===`). `$other` must be a `Complex`, `int`, or `float` —
an `int`/`float` is treated as a real number for the comparison. A type that can't meaningfully be compared throws,
rather than silently returning `false`, to catch bugs. `NAN` also throws, since it has no meaningful equality result;
`±INF` is not a type error, so it returns `false` instead — a `Complex` (always finite) is simply never equal to
infinity.

**Parameters:**

- `$other` (mixed) - The value to compare with (`Complex`, `int`, or `float`).

**Returns:**

- `bool` - True if exactly equal, false otherwise.

**Throws:**

- `InvalidArgumentException` if `$other` is not a `Complex`, `int`, or `float`.
- `DomainException` if `$other` is `NAN`.

**Examples:**

```php
$z1 = new Complex(3, 4);
$z2 = new Complex(3, 4);
$z3 = new Complex(3.0000000001, 4);

var_dump($z1->equal($z2));  // true (exact match)
var_dump($z1->equal($z3));  // false (not exact)
var_dump($z1->equal(5));    // false (z1 is not real)

// Real Complex numbers can equal int/float
$z4 = new Complex(5, 0);
var_dump($z4->equal(5));    // true
var_dump($z4->equal(5.0));  // true

// A Complex is never equal to infinity, but this isn't a type error, so it returns false rather than throwing
var_dump($z1->equal(INF));  // false
var_dump($z1->equal(-INF)); // false

// Anything else throws, rather than silently returning false
$z1->equal(NAN);      // throws DomainException (no meaningful equality result)
$z1->equal('3+4i');   // throws InvalidArgumentException
$z1->equal([3, 4]);   // throws InvalidArgumentException
$z1->equal(null);     // throws InvalidArgumentException
```

### approxEqual()

```php
public function approxEqual(
    mixed $other,
    float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
    float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
): bool
```

Check if this complex number approximately equals another value within specified tolerances.

Uses combined relative and absolute tolerance approach, comparing both real and imaginary components separately.
`$other` must be a `Complex`, `int`, or `float`, same as `equal()`. `NAN` throws for the same reason as `equal()` (no
meaningful result); `±INF` returns `false` rather than throwing.

**Parameters:**

- `$other` (mixed) - The value to compare with (`Complex`, `int`, or `float`).
- `$relTol` (float) - Relative tolerance (default: 1e-9).
- `$absTol` (float) - Absolute tolerance (default: PHP_FLOAT_EPSILON ≈ 2.22e-16).

**Returns:**

- `bool` - True if approximately equal within tolerances, false otherwise.

**Throws:**

- `InvalidArgumentException` if `$other` is not a `Complex`, `int`, or `float`.
- `DomainException` if `$other` is `NAN`.

**How tolerance works:**

- For each component, checks: `|a - b| ≤ max(relTol * max(|a|, |b|), absTol)`.
- Relative tolerance matters for large values.
- Absolute tolerance matters for values near zero.
- Both components must be within tolerance.

**Examples:**

```php
$z1 = new Complex(3, 4);
$z2 = new Complex(3.0000000001, 4.0000000001);

// Within default tolerance
var_dump($z1->approxEqual($z2));  // true

// With a tolerance too tight for the same difference
var_dump($z1->approxEqual($z2, 1e-15, 1e-15));  // false

// With zero tolerances (exact match required)
var_dump($z1->approxEqual($z1, 0.0, 0.0));  // true
var_dump($z1->approxEqual($z2, 0.0, 0.0));  // false

// Works with real numbers
$z3 = new Complex(5, 0);
var_dump($z3->approxEqual(5.0000001, 1e-6));  // true

// Anything else throws, rather than silently returning false
$z1->approxEqual('3.0000000001+4.0000000001i');  // throws InvalidArgumentException
```

---

## Unary Arithmetic Methods

### neg()

```php
public function neg(): self
```

Get the negative of this complex number.

**Example:**

```php
$z = new Complex(3, 4);
$result = $z->neg();  // -3 - 4i
```

### inv()

```php
public function inv(): self
```

Get the multiplicative inverse (reciprocal).

**Example:**

```php
$z = new Complex(3, 4);
$result = $z->inv();  // 0.12 - 0.16i
```

**Throws:** `ArithmeticException` if the number is zero.

### conj()

```php
public function conj(): self
```

Get the complex conjugate (negate the imaginary part).

**Example:**

```php
$z = new Complex(3, 4);
$result = $z->conj();  // 3 - 4i
```

---

## Binary Arithmetic Methods

### add()

```php
public function add(self|float $other): self
```

Add another value to this complex number.

**Example:**

```php
$z1 = new Complex(3, 4);
$z2 = new Complex(1, 2);
$sum = $z1->add($z2);  // 4 + 6i
```

**Throws:** `DomainException` if `$other` is a non-finite float (±INF or NAN).

### sub()

```php
public function sub(self|float $other): self
```

Subtract another value from this complex number.

**Example:**

```php
$z1 = new Complex(5, 7);
$z2 = new Complex(2, 3);
$diff = $z1->sub($z2);  // 3 + 4i
```

**Throws:** `DomainException` if `$other` is a non-finite float (±INF or NAN).

### mul()

```php
public function mul(self|float $other): self
```

Multiply this complex number by another value.

**Example:**

```php
$z = new Complex(3, 4);
$result = $z->mul(2);  // 6 + 8i

$z1 = new Complex(1, 2);
$z2 = new Complex(3, 4);
$product = $z1->mul($z2);  // -5 + 10i
```

**Throws:** `DomainException` if `$other` is a non-finite float (±INF or NAN).

### div()

```php
public function div(self|float $other): self
```

Divide this complex number by another value.

**Example:**

```php
$z = new Complex(6, 8);
$result = $z->div(2);  // 3 + 4i

$z1 = new Complex(1, 2);
$z2 = new Complex(3, 4);
$quotient = $z1->div($z2);
```

**Throws:**

- `DomainException` if `$other` is a non-finite float (±INF or NAN).
- `ArithmeticException` if dividing by zero.

---

## Power Methods

### pow()

```php
public function pow(self|float $other): self
```

Raise this complex number to a power.

**Examples:**

```php
$z = new Complex(3, 4);
$result = $z->pow(2);  // -7 + 24i

$result = M_I->pow(2);  // -1 + 0i
```

**Special cases:**

- z^0 = 1 for any z (including 0 by convention)
- 0^(positive) = 0
- 0^(negative or complex) throws `ArithmeticException`

**Throws:**

- `DomainException` if `$other` is a non-finite float (±INF or NAN).
- `ArithmeticException` if attempting 0 raised to a negative or complex power.

### sqr()

```php
public function sqr(): self
```

Calculate the square of this complex number.

**Example:**

```php
$z = new Complex(3, 4);
$result = $z->sqr();  // -7 + 24i
```

---

## Root Methods

### roots()

```php
public function roots(int $degree): array
```

Calculate all nth roots of this complex number.

**Parameters:**

- `$degree` (int) - The degree of the root, e.g. 2 for square root, 3 for cube root (must be positive).

**Returns:**

- `list<self>` - Array of `$degree` complex roots.

**Examples:**

```php
// Cube roots of 1
$z = new Complex(1);
$roots = $z->roots(3);  // Returns 3 roots

// Square roots of -1
$z = new Complex(-1);
$roots = $z->roots(2);  // Returns [i, -i]
```

**Throws:** `DomainException` if `$degree` is not a positive integer.

### sqrt()

```php
public function sqrt(): self
```

Calculate the principal square root.

**Example:**

```php
$z = new Complex(-1);
$result = $z->sqrt();  // 0 + 1i
```

---

## Transcendental Methods

### exp()

```php
public function exp(): self
```

Calculate e raised to the power of this complex number.

**Example:**

```php
$z = new Complex(0, M_PI);
$result = $z->exp();  // -1 + 0i (Euler's identity)
```

### ln()

```php
public function ln(): self
```

Calculate the natural logarithm.

**Example:**

```php
$z = new Complex(3, 4);
$result = $z->ln();
```

**Throws:** `ArithmeticException` if the number is zero.

### log()

```php
public function log(self|float $base): self
```

Calculate logarithm with specified base using change of base formula: log_b(z) = ln(z) / ln(b).

**Example:**

```php
$z = new Complex(8);
$result = $z->log(2);  // 3 + 0i (log₂(8) = 3)
```

**Throws:**

- `DomainException` if `$base` is a non-finite float (±INF or NAN).
- `ArithmeticException` if the base is 0 or 1, or if this number is zero.

---

## Trigonometric Methods

### sin(), cos(), tan()

```php
public function sin(): self;
public function cos(): self;
public function tan(): self;
```

Calculate trigonometric functions.

**Examples:**

```php
$z = new Complex(1, 1);
$sin = $z->sin();
$cos = $z->cos();
$tan = $z->tan();
```

---

## Inverse Trigonometric Methods

### asin(), acos(), atan()

```php
public function asin(): self;
public function acos(): self;
public function atan(): self;
```

Calculate inverse trigonometric functions.

**Examples:**

```php
$z = new Complex(0.5);
$asin = $z->asin();
$acos = $z->acos();
$atan = $z->atan();
```

---

## Hyperbolic Methods

### sinh(), cosh(), tanh()

```php
public function sinh(): self;
public function cosh(): self;
public function tanh(): self;
```

Calculate hyperbolic functions.

**Examples:**

```php
$z = new Complex(1, 1);
$sinh = $z->sinh();
$cosh = $z->cosh();
$tanh = $z->tanh();
```

---

## Inverse Hyperbolic Methods

### asinh(), acosh(), atanh()

```php
public function asinh(): self;
public function acosh(): self;
public function atanh(): self;
```

Calculate inverse hyperbolic functions.

**Examples:**

```php
$z = new Complex(0.5);
$asinh = $z->asinh();
$acosh = $z->acosh();
$atanh = $z->atanh();
```

---

## Rounding Methods

### round()

```php
public function round(int $precision, RoundingMode $mode = RoundingMode::HalfAwayFromZero): self
```

Round the real and imaginary parts to the given number of decimal places, using the specified rounding mode.
Defaults to "half away from zero", matching the default mode used by PHP's own `round()` function.

**Parameters:**

- `$precision` (int) - The number of decimal places to round to. Must not be negative.
- `$mode` (RoundingMode) - The rounding mode to use.

**Returns:**

- `self` - A new complex number with both parts rounded.

**Throws:**

- `DomainException` if `$precision` is negative.

**Examples:**

```php
$z = new Complex(7 / 3, 8 / 3);
echo $z->round(0);  // 2 + 3i (2.333... rounds down, 2.666... rounds up)

$z2 = new Complex(1.2345, -1.2345);
echo $z2->round(2);  // 1.23 - 1.23i
```

**Rounding modes:**

```php
$z = new Complex(2.5, -2.5);

$z->round(0, RoundingMode::TowardsZero);      // 2 - 2i
$z->round(0, RoundingMode::AwayFromZero);     // 3 - 3i
$z->round(0, RoundingMode::NegativeInfinity); // 2 - 3i (equivalent to floor() on each part)
$z->round(0, RoundingMode::PositiveInfinity); // 3 - 2i (equivalent to ceil() on each part)
$z->round(0, RoundingMode::HalfAwayFromZero); // 3 - 3i (the default)
$z->round(0, RoundingMode::HalfTowardsZero);  // 2 - 2i
$z->round(0, RoundingMode::HalfEven);         // 2 - 2i ("banker's rounding": ties go to the nearest even integer)
$z->round(0, RoundingMode::HalfOdd);          // 3 - 3i (ties go to the nearest odd integer)
```

---

## ArrayAccess Methods

Complex numbers can be accessed as arrays:

```php
$z = new Complex(3, 4);

// Read access
echo $z[0];  // 3.0 (real part)
echo $z[1];  // 4.0 (imaginary part)

// Check existence
var_dump(isset($z[0]));  // true
var_dump(isset($z[2]));  // false

// Invalid offsets throw rather than returning null/false
$z[2];         // Throws OutOfRangeException (only 0 and 1 are valid)
$z['real'];    // Throws InvalidArgumentException (offset must be an int)

// Cannot modify (immutable)
$z[0] = 5;  // Throws LogicException
unset($z[0]);  // Throws LogicException
```

---

## Usage Examples

### Basic Complex Arithmetic

```php
$z1 = new Complex(3, 4);
$z2 = new Complex(1, 2);

$sum = $z1->add($z2);         // 4 + 6i
$diff = $z1->sub($z2);        // 2 + 2i
$product = $z1->mul($z2);     // -5 + 10i
$quotient = $z1->div($z2);    // 2.2 - 0.4i
```

### Polar Form Conversion

```php
// Create from polar coordinates
$z = Complex::fromPolar(5, M_PI / 4);

// Access polar properties
echo $z->magnitude;  // 5.0
echo $z->phase;      // 0.785... (π/4)
```

### Euler's Identities

```php
use const OceanMoon\Core\Globals\M_TAU;

// e^(iπ) = -1
$z = new Complex(0, M_PI);
$result = $z->exp();  // -1 + 0i

// e^(iτ) = 1
$z = new Complex(0, M_TAU);
$result = $z->exp();  // 1 + 0i
```

### Finding Roots

```php
// Find all cube roots of 8
$eight = new Complex(8);
$roots = $eight->roots(3);

echo $roots[0];  // "2"                      (the real cube root)
echo $roots[1];  // "-1 + 1.7320508075689i"  (-1 + √3 i)
echo $roots[2];  // "-1 - 1.7320508075689i"  (-1 - √3 i)

// Verify: each root cubed equals 8
$roots[1]->pow(3)->approxEqual(8);  // true
```

### Complex Trigonometry

```php
$z = new Complex(1, 1);
$sin = $z->sin();
$cos = $z->cos();

// Verify Pythagorean identity: sin²(z) + cos²(z) = 1
$sin2 = $sin->sqr();
$cos2 = $cos->sqr();
$sum = $sin2->add($cos2);  // 1 + 0i
```

---

## See Also

- **[Rational](Rational.md)** - Exact rational number arithmetic
- **[Matrix](Matrix.md)** - Matrix operations (can contain complex-valued computations)
- **[Vector](Vector.md)** - Vector operations
- **[Floats](https://github.com/mossy2100/PHP-Core/blob/main/docs/Floats.md)** - Float utilities including approximate
  comparison
- **`M_TAU`** - The `OceanMoon\Core\Globals\M_TAU` constant (2π), used by `roots()` and `exp()`
