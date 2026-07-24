# Rational

Immutable class representing rational numbers as exact integer ratios with automatic simplification.

---

## Overview

The `Rational` class provides exact representation of rational numbers using two PHP integers for the numerator and denominator. Key features include:
- Automatic reduction to simplest form (e.g., 6/8 → 3/4)
- Canonical form (positive denominator, sign in numerator)
- Exact arithmetic without floating-point errors
- Conversion to/from floats using continued fractions
- Comparison operations with support for mixed types
- Overflow detection for safe integer arithmetic

**Valid range:** The absolute value can range from 1/PHP_INT_MAX to PHP_INT_MAX/1. Neither the numerator nor denominator can be PHP_INT_MIN.

---

## Properties

### $numerator

```php
private(set) int $numerator
```

The numerator. Always in canonical form (sign stored here). Read-only from outside the class.

### $denominator

```php
private(set) int $denominator
```

The denominator. Always positive in canonical form. Read-only from outside the class.

---

## Constructor

```php
public function __construct(int $num = 0, int $den = 1)
```

Create a new rational number from exact integers. This constructor is deliberately narrow — for
converting a float, use [`fromFloat()`](#fromfloat) instead, which handles approximation via
continued fractions.

**Parameters:**
- `$num` (int) - The numerator (default: 0).
- `$den` (int) - The denominator (default: 1).

**Behavior:**
- Automatically reduces the fraction to simplest form.
- Moves a negative denominator's sign to the numerator (e.g. 3/-4 → -3/4); double negatives cancel
  (e.g. -3/-4 → 3/4).

**Examples:**
```php
$r1 = new Rational(3, 4);        // 3/4
$r2 = new Rational(6, 8);        // Automatically reduced to 3/4
$r3 = new Rational(5);           // 5/1 (integer)
$r4 = new Rational(1, 3);        // 1/3
$r5 = new Rational(-3, 4);       // -3/4
$r6 = new Rational(3, -4);       // -3/4 (sign moved to numerator)
```

**Valid range:** The absolute value can range from 1/PHP_INT_MAX to PHP_INT_MAX/1.

```php
new Rational(1, 0);               // ArithmeticException (zero denominator)
```

**Throws:**
- `ArithmeticException` if the denominator is zero.
- `DomainException` if the ratio can't be exactly represented (PHP_INT_MIN paired with an odd or
  otherwise incompatible counterpart, e.g. `new Rational(PHP_INT_MIN, 3)`). This is not a magnitude
  problem — the resulting value may well be within the representable range — it's specifically that
  exact integer ratio that can't be reduced, since PHP_INT_MIN can't be safely negated. Use
  `fromFloat()` if an approximation is acceptable instead.

---

## Factory Methods

### fromFloat()

```php
public static function fromFloat(float $value): self
```

Create a Rational from a float, approximating it if necessary. A float that's actually a whole
number (e.g. `3.0`) converts directly and exactly; otherwise, it's approximated using continued
fractions.

**Examples:**
```php
$r1 = Rational::fromFloat(0.5);         // 1/2
$r2 = Rational::fromFloat(0.75);        // 3/4
$r3 = Rational::fromFloat(1 / 3);       // 1/3 (exact despite 0.333... input)
```

Irrational numbers are approximated as closely as possible within the integer range:
```php
$r = Rational::fromFloat(M_PI);
echo $r;                          // "245850922/78256779"
echo $r->toFloat();               // 3.1415926535898 (indistinguishable from M_PI)
```

**Valid range:** The absolute value can range from 1/PHP_INT_MAX to PHP_INT_MAX/1. Values outside this range throw:
```php
Rational::fromFloat(1e19);              // DomainException — too large
Rational::fromFloat(1e-20);             // DomainException — too small
Rational::fromFloat(INF);               // DomainException — non-finite
Rational::fromFloat(NAN);               // DomainException — non-finite
```

**Throws:** `DomainException` if the value is not finite (±INF or NAN), or is outside the
representable range (too large or too small, but non-zero).

### fromString()

```php
public static function fromString(string $str): self
```

Create a Rational from a string.

**Supported formats:**
- Integers: `"123"`, `"-456"`
- Floats: `"3.14"`, `"-0.25"`
- Fractions: `"3/4"`, `"-5/6"`, `" 7 / 8 "`

**Examples:**
```php
$r1 = Rational::fromString("3/4");       // 3/4
$r2 = Rational::fromString("0.5");       // 1/2
$r3 = Rational::fromString("-5");        // -5/1
$r4 = Rational::fromString(" 6 / 8 ");   // 3/4 (whitespace OK, auto-reduced)
```

**Throws:**
- `FormatException` if the string is empty or does not match a supported format.
- `ArithmeticException` if the string is a fraction with a zero denominator (e.g. `"1/0"`).
- `DomainException` if the string represents a value outside the representable range (see `fromFloat()`
  above).

---

## Conversion Methods

### toFloat()

```php
public function toFloat(): float
```

Convert the rational number to a float.

**Example:**
```php
$r = new Rational(1, 2);
echo $r->toFloat();  // 0.5

$r2 = new Rational(1, 3);
echo $r2->toFloat();  // 0.33333...
```

### toMixedNumber()

```php
public function toMixedNumber(): array
```

Convert to a mixed number representation: an integer part and a fractional part. Uses trunc/frac semantics — the integer part truncates toward zero, and the fractional part carries the same sign as the original. In this way, the two can be added to reconstruct the original value.

**Returns:**
- `array{int, self}` - A tuple of [integer part, fractional remainder].

**Examples:**
```php
$r = new Rational(9, 4);
[$int, $frac] = $r->toMixedNumber();
echo $int;   // 2
echo $frac;  // "1/4"

$r2 = new Rational(-9, 4);
[$int, $frac] = $r2->toMixedNumber();
echo $int;   // -2
echo $frac;  // "-1/4"

// Proper fraction (no integer part)
$r3 = new Rational(3, 4);
[$int, $frac] = $r3->toMixedNumber();
echo $int;   // 0
echo $frac;  // "3/4"

// Reconstruct: integer + fraction = original
$r4 = new Rational(-11, 3);
[$int, $frac] = $r4->toMixedNumber();
$frac->add($int)->equal($r4);  // true
```

### \_\_toString()

```php
public function __toString(): string
```

Convert to string representation.

**Format:**
- Whole numbers: `"5"`, `"-3"`
- Fractions: `"3/4"`, `"-5/6"`

**Examples:**
```php
echo new Rational(5, 1);   // "5"
echo new Rational(3, 4);   // "3/4"
echo new Rational(-2, 5);  // "-2/5"
echo new Rational(6, 8);   // "3/4" (auto-reduced)
```

---

## Comparison Methods

The `equal()`, `approxEqual()`, `compare()`, and `approxCompare()` methods are provided by the
[`ApproxComparable`](https://github.com/mossy2100/PHP-Core/blob/main/docs/Traits/Comparison/ApproxComparable.md) trait
from the [Core](https://github.com/mossy2100/PHP-Core) package, with `Rational` supplying `compare()` and
`approxEqual()` (the trait's abstract methods) plus its own type-checking logic.

All comparison methods accept only a `Rational`, `int`, or `float` for `$other` — not a `string`, even a
Rational-format one like `"1/2"`. Anything else throws `InvalidArgumentException` rather than silently
returning `false`, to catch bugs from comparing values that can't meaningfully be compared. Use
[`fromString()`](#fromstring) explicitly first if you need to compare against a string.

### equal()

```php
public function equal(mixed $other): bool
```

Check if this rational number exactly equals another value.

Uses exact comparison based on `compare()` returning 0.

**Parameters:**
- `$other` (mixed) - The value to compare with (`Rational`, `int`, or `float`).

**Returns:**
- `bool` - True if exactly equal, false otherwise.

**Throws:**
- `InvalidArgumentException` if `$other` is not a `Rational`, `int`, or `float`.
- `DomainException` if `$other` is `NAN`.

**Examples:**
```php
$r1 = new Rational(3, 4);
$r2 = new Rational(6, 8);  // Reduced to 3/4
$r3 = new Rational(1, 2);

var_dump($r1->equal($r2));  // true (both are 3/4)
var_dump($r1->equal($r3));  // false
var_dump($r1->equal(0.75)); // true (exact match)
var_dump($r1->equal(0.7500000001)); // false (not exact)

// Anything else throws, rather than silently returning false
$r1->equal('3/4');  // throws InvalidArgumentException
$r1->equal(null);   // throws InvalidArgumentException
```

### approxEqual()

```php
public function approxEqual(
    mixed $other,
    float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
    float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
): bool
```

Check if this rational number approximately equals another value within specified tolerances.

Converts both values to floats and uses combined relative and absolute tolerance approach. `NAN` throws, since it has
no meaningful equality result; `±INF` returns `false` instead — a `Rational` (always finite) is never approximately
equal to infinity.

**Parameters:**
- `$other` (mixed) - The value to compare with (`Rational`, `int`, or `float`).
- `$relTol` (float) - Relative tolerance (default: 1e-9).
- `$absTol` (float) - Absolute tolerance (default: PHP_FLOAT_EPSILON ≈ 2.22e-16).

**Returns:**
- `bool` - True if approximately equal within tolerances, false otherwise.

**Throws:**
- `InvalidArgumentException` if `$other` is not a `Rational`, `int`, or `float`.
- `DomainException` if `$other` is `NAN`.

**How tolerance works:**
- Checks: `|a - b| ≤ max(relTol * max(|a|, |b|), absTol)`.
- Relative tolerance matters for large values.
- Absolute tolerance matters for values near zero.

**Examples:**
```php
$r1 = new Rational(1, 3);
$r2 = new Rational(333333, 1000000);

// Within loose tolerance
var_dump($r1->approxEqual($r2, 1e-5, 1e-5));  // true

// Outside tight tolerance
var_dump($r1->approxEqual($r2, 1e-9, 1e-9));  // false

// Works with floats
$r3 = new Rational(1, 2);
var_dump($r3->approxEqual(0.5000001, 1e-5));  // true

// With zero tolerances (exact match required)
var_dump($r1->approxEqual($r1, 0.0, 0.0));  // true

// A Rational is never approximately equal to infinity, but this isn't a type error, so it returns false
var_dump($r1->approxEqual(INF));   // false
var_dump($r1->approxEqual(-INF));  // false

// Anything else throws, rather than silently returning false
$r1->approxEqual(NAN);              // throws DomainException (no meaningful equality result)
$r1->approxEqual('not a number');  // throws InvalidArgumentException
```

### compare()

```php
public function compare(mixed $other): int
```

Compare this rational number with another value using exact comparison.

**Parameters:**
- `$other` (mixed) - The value to compare with (`Rational`, `int`, or `float`).

**Returns:**
- `int` - Exactly -1, 0, or 1.

**Behavior:**
- Optimizes comparison with integers and simple floats.
- Uses cross-multiplication for two Rationals: a/b vs c/d → compare a×d with b×c.
- Falls back to float comparison if overflow occurs.
- Returns 0 for exact equality (no epsilon needed - integers are exact).
- Works with ±INF: comparing against `INF` always returns -1, and against `-INF` always returns 1.

**Examples:**
```php
$r1 = new Rational(1, 2);
$r2 = new Rational(1, 3);

echo $r1->compare($r2);   // 1 (1/2 > 1/3)
echo $r1->compare(0.5);   // 0 (1/2 == 0.5 exactly)
echo $r2->compare(1);     // -1 (1/3 < 1)

// Works with Rational objects
$r3 = new Rational(2, 4);
echo $r1->compare($r3);   // 0 (1/2 == 2/4)
```

**Throws:**
- `InvalidArgumentException` if `$other` is not a `Rational`, `int`, or `float`.
- `DomainException` if `$other` is `NAN`.

### approxCompare()

```php
public function approxCompare(
    mixed $other,
    float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
    float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
): int
```

Compare this rational number with another value using approximate equality.

Returns 0 if values are approximately equal within tolerances, otherwise performs exact less/greater than comparison.

**Parameters:**
- `$other` (mixed) - The value to compare with (`Rational`, `int`, or `float`).
- `$relTol` (float) - Relative tolerance (default: 1e-9).
- `$absTol` (float) - Absolute tolerance (default: PHP_FLOAT_EPSILON ≈ 2.22e-16).

**Returns:**
- `int` - Exactly -1, 0, or 1.

**Examples:**
```php
$r1 = new Rational(1, 3);
$r2 = new Rational(333333, 1000000);

// Approximately equal within tolerance
echo $r1->approxCompare($r2, 1e-5, 1e-5);  // 0

// Outside tolerance, performs exact comparison
echo $r1->approxCompare($r2, 1e-9, 1e-9);  // 1 (1/3 > 333333/1000000)

// Use in sorting with approximate equality
$r3 = new Rational(1, 4);
echo $r3->approxCompare($r1);  // -1 (1/4 < 1/3)
```

**Throws:**
- `InvalidArgumentException` if `$other` is not a `Rational`, `int`, or `float`.
- `DomainException` if `$other` is `NAN`.

### lessThan(), greaterThan(), etc.

```php
public function lessThan(mixed $other): bool
public function lessThanOrEqual(mixed $other): bool
public function greaterThan(mixed $other): bool
public function greaterThanOrEqual(mixed $other): bool
```

Ordering comparison methods provided by the `ApproxComparable` trait (via `Comparable`). These use exact comparison
via `compare()`, and so accept the same types (`Rational`, `int`, or `float`) and throw under the same conditions.

**Examples:**
```php
$r1 = new Rational(1, 3);
$r2 = new Rational(1, 2);

var_dump($r1->lessThan($r2));           // true
var_dump($r1->lessThanOrEqual($r2));    // true
var_dump($r2->greaterThan($r1));        // true
var_dump($r2->greaterThanOrEqual($r1)); // true

// Also works with int and float
var_dump($r1->lessThan(0.5));           // true (1/3 < 0.5)
var_dump($r2->greaterThan(0));          // true
```

---

## Unary Arithmetic Methods

### abs()

```php
public function abs(): self
```

Calculate the absolute value.

**Example:**
```php
$r = new Rational(-3, 4);
$result = $r->abs();  // 3/4
```

### neg()

```php
public function neg(): self
```

Calculate the negative of this rational number.

**Example:**
```php
$r = new Rational(3, 4);
$result = $r->neg();  // -3/4
```

### inv()

```php
public function inv(): self
```

Calculate the multiplicative inverse (reciprocal).

**Example:**
```php
$r = new Rational(3, 4);
$result = $r->inv();  // 4/3

$r2 = new Rational(-2, 5);
$result2 = $r2->inv();  // -5/2
```

**Throws:** `ArithmeticException` if the numerator is zero.

---

## Binary Arithmetic Methods

### add()

```php
public function add(self|int $other): self
```

Add another value to this rational number.

**Example:**
```php
$r1 = new Rational(1, 2);
$r2 = new Rational(1, 3);
$sum = $r1->add($r2);  // 5/6

$r3 = new Rational(3, 4);
$sum2 = $r3->add(2);   // 11/4
```

**Throws:**
- `OverflowException` if the result overflows an integer.
- `DomainException` if the result cannot be expressed as a Rational.

### sub()

```php
public function sub(self|int $other): self
```

Subtract another value from this rational number.

**Example:**
```php
$r1 = new Rational(3, 4);
$r2 = new Rational(1, 4);
$diff = $r1->sub($r2);  // 1/2
```

**Throws:**
- `OverflowException` if the result overflows an integer.
- `DomainException` if the result cannot be expressed as a Rational.

### mul()

```php
public function mul(self|int $other): self
```

Multiply this rational number by another value.

**Uses cross-cancellation** to prevent overflow when possible.

**Example:**
```php
$r1 = new Rational(2, 3);
$r2 = new Rational(3, 4);
$product = $r1->mul($r2);  // 1/2

$r3 = new Rational(3, 5);
$product2 = $r3->mul(6);   // 18/5
```

**Throws:**
- `OverflowException` if the result overflows an integer.
- `DomainException` if the result cannot be expressed as a Rational.

### div()

```php
public function div(self|int $other): self
```

Divide this rational number by another value.

**Uses cross-cancellation** to prevent overflow when possible, same as `mul()`.

**Example:**
```php
$r1 = new Rational(2, 3);
$r2 = new Rational(3, 4);
$quotient = $r1->div($r2);  // 8/9

$r3 = new Rational(3, 4);
$quotient2 = $r3->div(2);   // 3/8
```

**Throws:**
- `ArithmeticException` if dividing by zero.
- `OverflowException` if the result overflows an integer.
- `DomainException` if the result cannot be expressed as a Rational.

---

## Power Methods

### pow()

```php
public function pow(int $exponent): self
```

Raise this rational number to an integer power.

**Examples:**
```php
$r = new Rational(2, 3);
$result = $r->pow(2);   // 4/9

$r2 = new Rational(1, 2);
$result2 = $r2->pow(3);  // 1/8

$r3 = new Rational(2, 3);
$result3 = $r3->pow(-2); // 9/4 (negative exponent = reciprocal)

$r4 = new Rational(5, 7);
$result4 = $r4->pow(0);  // 1/1 (any number^0 = 1)
```

**Special cases:**
- n^0 = 1 (including 0^0 by convention)
- n^1 = n (returns a new, distinct object that's equal to but not the same instance as `$this`)
- 0^(positive) = 0
- 0^(negative) throws `ArithmeticException`

**Throws:**
- `ArithmeticException` if raising zero to a negative power.
- `OverflowException` if the result overflows an integer.
- `DomainException` if the result cannot be expressed as a Rational.

### sqr()

```php
public function sqr(): self
```

Square this rational number. Equivalent to `pow(2)`, but more efficient and readable.

**Example:**
```php
$r = new Rational(3, 4);
$result = $r->sqr();  // 9/16
```

**Throws:**
- `OverflowException` if the result overflows an integer.
- `DomainException` if the result cannot be expressed as a Rational.

---

## Rounding Methods

### round()

```php
public function round(RoundingMode $mode = RoundingMode::HalfAwayFromZero): int
```

Find the closest integer, using the specified rounding mode. Defaults to "half away from zero", matching the
default mode used by PHP's own `round()` function.

All arithmetic is performed exactly on the numerator and denominator -- the Rational is never converted to a
`float`, so there's no precision loss near tie boundaries or for a numerator/denominator beyond float's 53-bit
mantissa.

**Examples:**
```php
$r1 = new Rational(7, 3);
echo $r1->round();  // 2 (2.333...)

$r2 = new Rational(8, 3);
echo $r2->round();  // 3 (2.666...)

$r3 = new Rational(5, 2);
echo $r3->round();  // 3 (2.5 rounds away from zero, the default mode)

$r4 = new Rational(-5, 2);
echo $r4->round();  // -3 (-2.5 rounds away from zero)
```

**Rounding modes:**
```php
$r = new Rational(5, 2); // 2.5

$r->round(RoundingMode::TowardsZero);      // 2
$r->round(RoundingMode::AwayFromZero);     // 3
$r->round(RoundingMode::NegativeInfinity); // 2 (equivalent to floor())
$r->round(RoundingMode::PositiveInfinity); // 3 (equivalent to ceil())
$r->round(RoundingMode::HalfAwayFromZero); // 3 (the default)
$r->round(RoundingMode::HalfTowardsZero);  // 2
$r->round(RoundingMode::HalfEven);         // 2 ("banker's rounding": ties go to the nearest even integer)
$r->round(RoundingMode::HalfOdd);          // 3 (ties go to the nearest odd integer)
```

### floor()

```php
public function floor(): int
```

Find the largest integer less than or equal to this rational number.

**Examples:**
```php
$r1 = new Rational(7, 3);
echo $r1->floor();  // 2

$r2 = new Rational(-7, 3);
echo $r2->floor();  // -3
```

### ceil()

```php
public function ceil(): int
```

Find the smallest integer greater than or equal to this rational number.

**Examples:**
```php
$r1 = new Rational(7, 3);
echo $r1->ceil();  // 3

$r2 = new Rational(-7, 3);
echo $r2->ceil();  // -2
```

---

## Usage Examples

### Exact Arithmetic

```php
// No floating-point errors
$r1 = new Rational(1, 3);
$r2 = new Rational(1, 3);
$r3 = new Rational(1, 3);

$sum = $r1->add($r2)->add($r3);  // Exactly 1/1 (not 0.999...)
echo $sum;  // "1"
```

### Working with Fractions

```php
// Auto-reduction
$r = new Rational(6, 8);
echo $r;  // "3/4"

// Combining Rationals; convert a float first with fromFloat() if needed
$r1 = new Rational(1, 2);
$r2 = $r1->add(new Rational(1, 4));  // 1/2 + 1/4
echo $r2;                            // "3/4"

// Complex calculations
$r = new Rational(2, 3);
$result = $r->sqr()->mul(new Rational(9, 4));
echo $result;  // "1"
```

### Safe Integer Arithmetic

```php
try {
    $r = new Rational(PHP_INT_MAX, 1);
    $r2 = $r->add(1);  // Would overflow
} catch (OverflowException $e) {
    echo "Overflow detected!";
}
```

### Float Conversion

```php
// Convert problematic float calculations to exact rationals
$f = 0.1 + 0.2;  // 0.30000000000000004 (float error)
$r = Rational::fromFloat($f);
echo $r;  // "3/10" (exact)
```

### Comparing Rationals

```php
$r1 = new Rational(1, 2);
$r2 = new Rational(2, 4);  // Same as 1/2
$r3 = new Rational(1, 3);

var_dump($r1->equal($r2));      // true
var_dump($r1->greaterThan($r3)); // true
var_dump($r3->lessThan(0.5));  // true (can compare with floats)
```

---

## See Also

- **[Complex](Complex.md)** - Complex number arithmetic
- **[Matrix](Matrix.md)** - Matrix operations
- **[Vector](Vector.md)** - Numeric vectors
- **[Floats](https://github.com/mossy2100/PHP-Core/blob/main/docs/Floats.md)** - Float utilities including approximate comparison
