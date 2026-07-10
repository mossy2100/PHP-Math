# Complex

Immutable class representing complex numbers with comprehensive mathematical operations.

---

## Overview

The `Complex` class provides a complete implementation of complex number arithmetic with support for:
- Basic arithmetic (addition, subtraction, multiplication, division)
- Transcendental functions (exponential, logarithm, power, roots)
- Trigonometric and hyperbolic functions
- Conversion between rectangular (a + bi) and polar (r∠θ) forms
- Conversion to/from arrays and plain objects
- Native PHP serialization and JSON encoding support
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

The phase (argument or angle) of the complex number in radians, normalized to the principal value range **(-π, π]**. Automatically computed and cached on first access.

For z = a + bi: arg(z) = atan2(b, a), then wrapped to (-π, π]

The range excludes -π but includes π, following the standard mathematical convention for complex number arguments.

---

## Constructor

```php
public function __construct(float $real = 0, float $imag = 0)
```

Create a new complex number from real and imaginary parts.

**Parameters:**
- `$real` (float) - The real part (default: 0)
- `$imag` (float) - The imaginary part (default: 0)

**Examples:**
```php
$z1 = new Complex(3, 4);        // 3 + 4i
$z2 = new Complex(5);           // 5 + 0i (real number)
$z3 = new Complex(0, 2);        // 0 + 2i (pure imaginary)
$z4 = new Complex();            // 0 + 0i (zero)
```

**Note:** To create a complex number from a string, use the `parse()` method.

---

## Factory Methods

These are all static methods that return a `Complex`.

### fromArray()

```php
public static function fromArray(array $arr): self
```

Create a Complex from an array, which can be either a list `[real, imaginary]` or an associative
array with `'real'` and `'imaginary'` keys. The associative form accepts the result of
`(array) $complex`, which also includes `'magnitude'` and `'phase'` keys; these are ignored.

**Example:**
```php
$z = Complex::fromArray([3, 4]);                             // 3 + 4i
$z = Complex::fromArray(['real' => 3, 'imaginary' => 4]);    // 3 + 4i
```

**Throws:**
- `LengthException` if the array is a list and doesn't contain exactly two elements.
- `DomainException` if the array is missing the `'real'` or `'imaginary'` key, or if either value is
  not numeric.

### fromObject()

```php
public static function fromObject(object $obj): self
```

Create a Complex from a plain object with numeric `real` and `imaginary` properties.

**Example:**
```php
$obj = (object)['real' => 3, 'imaginary' => 4];
$z = Complex::fromObject($obj);  // 3 + 4i
```

**Throws:** `DomainException` if the object is missing the `real` or `imaginary` property, or if
either value is not numeric.

### fromPolar()

```php
public static function fromPolar(float $mag, float $phase): self
```

Create a complex number from polar coordinates (magnitude and phase).

**Parameters:**
- `$mag` (float) - The magnitude (r)
- `$phase` (float) - The phase angle in radians

**Examples:**
```php
// Create from magnitude and phase
$z1 = Complex::fromPolar(5, M_PI / 4);
```

### parse()

```php
public static function parse(string $str): self
```

Parse a complex number from a string. Supports various formats.

**Supported formats:**
- Real numbers: `"5"`, `"-3.14"`
- Pure imaginary: `"i"`, `"j"`, `"3i"`, `"-2.5j"`
- Complex (real first): `"3+4i"`, `"5-2j"`, `"-1+i"`
- Complex (imaginary first): `"4i+3"`, `"-2j+5"`, `"i-1"`
- Whitespace tolerant: `" 3 + 4i "`, `"5 - 2j"`
- Case insensitive: `"I"`, `"J"`

**Examples:**
```php
$z1 = Complex::parse("3+4i");
$z2 = Complex::parse("-2.5j");
$z3 = Complex::parse("i");
$z4 = Complex::parse("4i+3");
```

**Throws:** [`FormatException`](https://github.com/mossy2100/PHP-Core/blob/main/docs/Exceptions/FormatException.md) if the string is invalid.

### toComplex()

```php
public static function toComplex(mixed $value): self
```

Convert a value to a Complex, if it isn't one already. This is the general-purpose conversion
method used internally by the arithmetic methods to accept `self|float` arguments, but it accepts
a broader range of types directly: an existing `Complex` is returned unchanged; an `int` or `float`
becomes a real Complex; a `string` is parsed via `parse()`; an `array` is converted via `fromArray()`
(list or associative); and an `object` is converted via `fromObject()`.

**Examples:**
```php
$z1 = Complex::toComplex(5);            // 5 + 0i
$z2 = Complex::toComplex('3+4i');       // 3 + 4i
$z3 = Complex::toComplex([3, 4]);       // 3 + 4i
$z4 = Complex::toComplex((object)['real' => 3, 'imaginary' => 4]);  // 3 + 4i
```

**Throws:**
- `InvalidArgumentException` if the value's type cannot be converted to a Complex.
- `LengthException` if the value is a list array and doesn't contain exactly two elements.
- `DomainException` if an array or object does not have the required structure.
- [`FormatException`](https://github.com/mossy2100/PHP-Core/blob/main/docs/Exceptions/FormatException.md) if a string cannot be parsed.

---

## Conversion Methods

### toArray()

```php
public function toArray(): array
```

Convert to list array with two floats: \[real, imaginary\]. NB: This is a different result than `(array) $complex`,
which will produce an associative array with keys "real", "imaginary", "magnitude", and "phase".

**Example:**
```php
$z = new Complex(3, 4);
$array = $z->toArray();  // [3.0, 4.0]
```

### toObject()

```php
public function toObject(): stdClass
```

Convert to a plain object (i.e. `stdClass`) with `real` and `imaginary` properties. NB: This is a different result than
`(object) $complex`, which will do nothing, i.e. it will simply return the same Complex object.

**Example:**
```php
$z = new Complex(3, 4);
$obj = $z->toObject();  // (object)['real' => 3.0, 'imaginary' => 4.0]
```

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

The `equal()` and `approxEqual()` methods are provided by the [`ApproxEquatable`](https://github.com/mossy2100/PHP-Core/blob/main/docs/Traits/Comparison/ApproxEquatable.md) trait from the [Core](https://github.com/mossy2100/PHP-Core) package. `identical()` is specific to `Complex`.

### identical()

```php
public function identical(mixed $other): bool
```

Check if this complex number is identical to another value: same type (`Complex`, not merely something
convertible to one) and exactly equal (`===`) real and imaginary parts.

Stricter than `equal()`, which accepts anything `toComplex()` can convert (`int`, `float`, `string`,
`array`, `object`). `identical()` only returns `true` for an actual `Complex` instance — mirroring the
distinction between PHP's `==` and `===`.

**Parameters:**
- `$other` (mixed) - The value to compare with.

**Returns:**
- `bool` - True if `$other` is a `Complex` with identical real and imaginary parts, false otherwise.

**Examples:**
```php
$z1 = new Complex(3, 4);
$z2 = new Complex(3, 4);

var_dump($z1->identical($z2));    // true
var_dump($z1->identical(3));      // false (not a Complex)
var_dump($z1->identical('3+4i')); // false (not a Complex, even though equal() would accept it)
```

### equal()

```php
public function equal(mixed $other): bool
```

Check if this complex number exactly equals another value.

Compares both real and imaginary parts using exact equality (`===`). `$other` is converted via
`toComplex()`, so anything that method accepts — `Complex`, `int`, `float`, a parseable `string`, a 2-element
`array`, or an `object` with numeric `real`/`imaginary` properties — can be compared. Returns `false`
for anything `toComplex()` can't convert, instead of throwing.

**Parameters:**
- `$other` (mixed) - The value to compare with (anything `toComplex()` accepts)

**Returns:**
- `bool` - True if exactly equal, false otherwise

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

// Also accepts anything toComplex() can convert: strings, arrays, and objects
var_dump($z1->equal('3+4i'));                              // true
var_dump($z1->equal([3, 4]));                               // true
var_dump($z1->equal(['real' => 3, 'imaginary' => 4]));      // true
var_dump($z1->equal((object)['real' => 3, 'imaginary' => 4])); // true

// Values that can't be converted return false
var_dump($z1->equal('not a number'));  // false
var_dump($z1->equal(null));            // false
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

Uses combined relative and absolute tolerance approach, comparing both real and imaginary components separately. `$other` is converted via `toComplex()`, so anything that method accepts — `Complex`, `int`, `float`, a parseable `string`, a 2-element `array`, or an `object` with numeric `real`/`imaginary` properties — can be compared. Returns `false` for anything `toComplex()` can't convert, instead of throwing.

**Parameters:**
- `$other` (mixed) - The value to compare with (anything `toComplex()` accepts)
- `$relTol` (float) - Relative tolerance (default: 1e-9)
- `$absTol` (float) - Absolute tolerance (default: PHP_FLOAT_EPSILON ≈ 2.22e-16)

**Returns:**
- `bool` - True if approximately equal within tolerances, false otherwise

**How tolerance works:**
- For each component, checks: `|a - b| ≤ max(relTol * max(|a|, |b|), absTol)`
- Relative tolerance matters for large values
- Absolute tolerance matters for values near zero
- Both components must be within tolerance

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

// Also accepts anything toComplex() can convert: strings, arrays, and objects
var_dump($z1->approxEqual('3.0000000001+4.0000000001i'));  // true

// Values that can't be converted return false
var_dump($z1->approxEqual('not a number'));  // false
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

**Throws:** `DivisionByZeroError` if the number is zero.

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

**Throws:** `DivisionByZeroError` if dividing by zero.

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

$result = I->pow(2);  // -1 + 0i
```

**Special cases:**
- z^0 = 1 for any z (including 0 by convention)
- 0^(positive) = 0
- 0^(negative or complex) throws `DomainException`

### roots()

```php
public function roots(int $n): array
```

Calculate all nth roots of this complex number.

**Parameters:**
- `$n` (int) - The degree of the root (must be positive)

**Returns:**
- `self[]` - Array of n complex roots

**Examples:**
```php
// Cube roots of 1
$z = new Complex(1);
$roots = $z->roots(3);  // Returns 3 roots

// Square roots of -1
$z = new Complex(-1);
$roots = $z->roots(2);  // Returns [i, -i]
```

**Throws:** `DomainException` if n ≤ 0.

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

**Throws:** `DomainException` if the number is zero.

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
- `DomainException` if base is 0 or 1
- `DomainException` if the number is zero

---

## Trigonometric Methods

### sin(), cos(), tan()

```php
public function sin(): self
public function cos(): self
public function tan(): self
```

Calculate trigonometric functions.

**Examples:**
```php
$z = new Complex(1, 1);
$sin = $z->sin();
$cos = $z->cos();
$tan = $z->tan();
```

### sec(), csc(), cot()

```php
public function sec(): self
public function csc(): self
public function cot(): self
```

Calculate secant, cosecant, and cotangent functions.

**Examples:**
```php
$z = new Complex(1, 1);
$sec = $z->sec();  // 1/cos(z)
$csc = $z->csc();  // 1/sin(z)
$cot = $z->cot();  // cos(z)/sin(z)
```

---

## Inverse Trigonometric Methods

### asin(), acos(), atan()

```php
public function asin(): self
public function acos(): self
public function atan(): self
```

Calculate inverse trigonometric functions.

**Examples:**
```php
$z = new Complex(0.5);
$asin = $z->asin();
$acos = $z->acos();
$atan = $z->atan();
```

### asec(), acsc(), acot()

```php
public function asec(): self
public function acsc(): self
public function acot(): self
```

Calculate inverse secant, cosecant, and cotangent functions.

**Examples:**
```php
$z = new Complex(2);
$asec = $z->asec();  // acos(1/z)
$acsc = $z->acsc();  // asin(1/z)
$acot = $z->acot();  // atan(1/z)
```

---

## Hyperbolic Methods

### sinh(), cosh(), tanh()

```php
public function sinh(): self
public function cosh(): self
public function tanh(): self
```

Calculate hyperbolic functions.

**Examples:**
```php
$z = new Complex(1, 1);
$sinh = $z->sinh();
$cosh = $z->cosh();
$tanh = $z->tanh();
```

### sech(), csch(), coth()

```php
public function sech(): self
public function csch(): self
public function coth(): self
```

Calculate hyperbolic secant, cosecant, and cotangent functions.

**Examples:**
```php
$z = new Complex(1, 1);
$sech = $z->sech();  // 1/cosh(z)
$csch = $z->csch();  // 1/sinh(z)
$coth = $z->coth();  // cosh(z)/sinh(z)
```

---

## Inverse Hyperbolic Methods

### asinh(), acosh(), atanh()

```php
public function asinh(): self
public function acosh(): self
public function atanh(): self
```

Calculate inverse hyperbolic functions.

**Examples:**
```php
$z = new Complex(0.5);
$asinh = $z->asinh();
$acosh = $z->acosh();
$atanh = $z->atanh();
```

### asech(), acsch(), acoth()

```php
public function asech(): self
public function acsch(): self
public function acoth(): self
```

Calculate inverse hyperbolic secant, cosecant, and cotangent functions.

**Examples:**
```php
$z = new Complex(2);
$asech = $z->asech();  // acosh(1/z)
$acsch = $z->acsch();  // asinh(1/z)
$acoth = $z->acoth();  // atanh(1/z)
```

---

## Serialization Methods

### \_\_serialize()

```php
public function __serialize(): array
```

Serialize to an associative array with `real` and `imaginary` keys. Used automatically by PHP's
`serialize()`. The computed `magnitude`/`phase` properties are deliberately excluded, since they
may not be set and are always recomputable from `real`/`imaginary`.

**Example:**
```php
$z = new Complex(3, 4);
$data = $z->__serialize();  // ['real' => 3.0, 'imaginary' => 4.0]
```

### \_\_unserialize()

```php
public function __unserialize(array $data): void
```

Restore a Complex from data produced by `__serialize()`. Used automatically by PHP's
`unserialize()`. Reconstructs via the constructor, so the usual finite-value validation applies to
unserialized data just as it does to normal construction.

**Example:**
```php
$z = new Complex(3, 4);
$restored = unserialize(serialize($z));
$restored->equal($z);  // true
```

**Throws:** `DomainException` if the data is missing the required keys, the values are not
numeric, or either value is not finite (±INF or NAN).

### jsonSerialize()

```php
public function jsonSerialize(): array
```

Provides `Complex`'s representation for `json_encode()`, via the `JsonSerializable` interface.
Returns the same associative array as `__serialize()`.

**Example:**
```php
$z = new Complex(3, 4);
echo json_encode($z);  // '{"real":3,"imaginary":4}'
```

---

## ArrayAccess Implementation

Complex numbers can be accessed as arrays:

```php
$z = new Complex(3, 4);

// Read access
echo $z[0];  // 3.0 (real part)
echo $z[1];  // 4.0 (imaginary part)

// Check existence
var_dump(isset($z[0]));  // true
var_dump(isset($z[2]));  // false

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
// e^(iπ) = -1
$z = new Complex(0, M_PI);
$result = $z->exp();  // -1 + 0i

// e^(iτ) = 1
$z = new Complex(0, Floats::TAU);
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
- **[Floats](https://github.com/mossy2100/PHP-Core/blob/main/docs/Floats.md)** - Float utilities including `TAU` constant and approximate comparison
