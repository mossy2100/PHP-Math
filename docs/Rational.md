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
- Native PHP serialization and JSON encoding support

**Valid range:** The absolute value can range from 1/PHP_INT_MAX to PHP_INT_MAX/1. Neither the numerator nor denominator can be PHP_INT_MIN.

---

## Properties

### numerator

```php
private(set) int $numerator
```

The numerator. Always in canonical form (sign stored here). Read-only from outside the class.

### denominator

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
- `$num` (int) - The numerator (default: 0)
- `$den` (int) - The denominator (default: 1)

**Behavior:**
- Automatically reduces the fraction to simplest form
- Converts negative denominators: -3/−4 → 3/4

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
new Rational(1, 0);               // DivisionByZeroError (zero denominator)
```

**Throws:**
- `DivisionByZeroError` if the denominator is zero
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

**Valid range:** The absolute value can range from 1/PHP_INT_MAX to PHP_INT_MAX/1. Values outside this range throw overflow or underflow exceptions:
```php
Rational::fromFloat(1e19);              // OverflowException — too large
Rational::fromFloat(1e-20);             // UnderflowException — too small
Rational::fromFloat(INF);               // DomainException — non-finite
Rational::fromFloat(NAN);               // DomainException — non-finite
```

**Throws:**
- `DomainException` if the value is not finite (±INF or NAN)
- `OverflowException` if the value is too large to represent as a Rational
- `UnderflowException` if the value is non-zero but too small to represent as a Rational

### parse()

```php
public static function parse(string $s): self
```

Parse a string into a rational number.

**Supported formats:**
- Integers: `"123"`, `"-456"`
- Floats: `"3.14"`, `"-0.25"`
- Fractions: `"3/4"`, `"-5/6"`, `" 7 / 8 "`

**Examples:**
```php
$r1 = Rational::parse("3/4");       // 3/4
$r2 = Rational::parse("0.5");       // 1/2
$r3 = Rational::parse("-5");        // -5/1
$r4 = Rational::parse(" 6 / 8 ");   // 3/4 (whitespace OK, auto-reduced)
```

**Throws:**
- [`FormatException`](https://github.com/mossy2100/PHP-Core/blob/main/docs/Exceptions/FormatException.md) if the string cannot be parsed
- `OverflowException` if the value is too large to represent as a Rational
- `UnderflowException` if the value is non-zero but too small to represent as a Rational

### toRational()

```php
public static function toRational(mixed $value): self
```

Convert a value to a Rational if it isn't one already. Accepts an existing `Rational` (returned
unchanged), an `int` or `float`, or a `string` in the format accepted by `parse()`.

**Examples:**
```php
$r1 = Rational::toRational(5);              // 5/1
$r2 = Rational::toRational(0.5);            // 1/2
$r3 = Rational::toRational("3/4");          // 3/4
$r4 = Rational::toRational(new Rational(2, 3)); // Returns same instance
```

**Throws:**
- `DomainException` if the argument is a non-finite float (±INF or NAN), or a string that doesn't represent a valid rational
- `OverflowException` if the value is too large to represent as a Rational
- `UnderflowException` if the value is non-zero but too small to represent as a Rational
- `InvalidArgumentException` if the value is not a Rational, int, float, or string

---

## Comparison Methods

The `equal()`, `approxEqual()`, `compare()`, and `approxCompare()` methods are provided by the [`ApproxComparable`](https://github.com/mossy2100/PHP-Core/blob/main/docs/Traits/Comparison/ApproxComparable.md) trait from the [Core](https://github.com/mossy2100/PHP-Core) package.

### equal()

```php
public function equal(mixed $other): bool
```

Check if this rational number exactly equals another value.

Uses exact comparison based on `compare()` returning 0. Returns `false` for invalid types instead of throwing.

**Parameters:**
- `$other` (mixed) - The value to compare with (int, float, or Rational)

**Returns:**
- `bool` - True if exactly equal, false otherwise

**Examples:**
```php
$r1 = new Rational(3, 4);
$r2 = new Rational(6, 8);  // Reduced to 3/4
$r3 = new Rational(1, 2);

var_dump($r1->equal($r2));  // true (both are 3/4)
var_dump($r1->equal($r3));  // false
var_dump($r1->equal(0.75)); // true (exact match)
var_dump($r1->equal(0.7500000001)); // false (not exact)

// Invalid types return false
var_dump($r1->equal("3/4")); // false
var_dump($r1->equal(null));  // false
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

Converts both values to floats and uses combined relative and absolute tolerance approach. Returns `false` for invalid types instead of throwing.

**Parameters:**
- `$other` (mixed) - The value to compare with (int, float, or Rational)
- `$relTol` (float) - Relative tolerance (default: 1e-9)
- `$absTol` (float) - Absolute tolerance (default: PHP_FLOAT_EPSILON ≈ 2.22e-16)

**Returns:**
- `bool` - True if approximately equal within tolerances, false otherwise

**How tolerance works:**
- Checks: `|a - b| ≤ max(relTol * max(|a|, |b|), absTol)`
- Relative tolerance matters for large values
- Absolute tolerance matters for values near zero

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

// Invalid types return false
var_dump($r1->approxEqual('string'));  // false
```

### compare()

```php
public function compare(mixed $other): int
```

Compare this rational number with another value using exact comparison.

**Parameters:**
- `$other` (mixed) - The value to compare with (int, float, or Rational)

**Returns:**
- `int` - Exactly -1, 0, or 1

**Behavior:**
- Optimizes comparison with integers and simple floats
- Uses cross-multiplication for two Rationals: a/b vs c/d → compare a×d with b×c
- Falls back to float comparison if overflow occurs
- Returns 0 for exact equality (no epsilon needed - integers are exact)

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

**Throws:** `IncomparableTypesException` if the value cannot be compared.

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
- `$other` (mixed) - The value to compare with (int, float, or Rational)
- `$relTol` (float) - Relative tolerance (default: 1e-9)
- `$absTol` (float) - Absolute tolerance (default: PHP_FLOAT_EPSILON ≈ 2.22e-16)

**Returns:**
- `int` - Exactly -1, 0, or 1

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

**Throws:** `IncomparableTypesException` if the value cannot be compared.

### lessThan(), greaterThan(), etc.

```php
public function lessThan(mixed $other): bool
public function lessThanOrEqual(mixed $other): bool
public function greaterThan(mixed $other): bool
public function greaterThanOrEqual(mixed $other): bool
```

Ordering comparison methods provided by the `ApproxComparable` trait. These use exact comparison via `compare()`.

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

**Throws:** `DivisionByZeroError` if the numerator is zero.

---

## Binary Arithmetic Methods

### add()

```php
public function add(int|float|self $other): self
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

**Throws:** `OverflowException` if the result overflows.

### sub()

```php
public function sub(int|float|self $other): self
```

Subtract another value from this rational number.

**Example:**
```php
$r1 = new Rational(3, 4);
$r2 = new Rational(1, 4);
$diff = $r1->sub($r2);  // 1/2
```

**Throws:** `OverflowException` if the result overflows.

### mul()

```php
public function mul(int|float|self $other): self
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

**Throws:** `OverflowException` if the result overflows.

### div()

```php
public function div(int|float|self $other): self
```

Divide this rational number by another value.

**Example:**
```php
$r1 = new Rational(2, 3);
$r2 = new Rational(3, 4);
$quotient = $r1->div($r2);  // 8/9

$r3 = new Rational(3, 4);
$quotient2 = $r3->div(2);   // 3/8
```

**Throws:**
- `DivisionByZeroError` if dividing by zero
- `OverflowException` if the result overflows

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
- 0^(positive) = 0
- 0^(negative) throws `DomainException`

**Throws:**
- `DomainException` if raising zero to a negative power
- `OverflowException` if the result overflows

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

**Throws:** `OverflowException` if the result overflows.

---

## Rounding Methods

### round()

```php
public function round(): int
```

Find the closest integer, using "half away from zero" rounding mode.

**Examples:**
```php
$r1 = new Rational(7, 3);
echo $r1->round();  // 2 (2.333...)

$r2 = new Rational(8, 3);
echo $r2->round();  // 3 (2.666...)

$r3 = new Rational(5, 2);
echo $r3->round();  // 3 (2.5 rounds away from zero)

$r4 = new Rational(-5, 2);
echo $r4->round();  // -3 (-2.5 rounds away from zero)
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

## Serialization Methods

### \_\_unserialize()

```php
public function __unserialize(array $data): void
```

Restore a Rational from serialized data. Used automatically by PHP's `unserialize()`. Reconstructs
via the constructor, so the usual validation and canonicalization (reduction to lowest terms) apply
to unserialized data just as they do to normal construction.

There is no corresponding `__serialize()` method: unlike `Complex` (which has computed
`magnitude`/`phase` properties that must be excluded from the serialized payload), `Rational`'s only
properties are `numerator` and `denominator`, so PHP's default serialization already produces
exactly the same payload a custom `__serialize()` would.

**Example:**
```php
$r = new Rational(3, 4);
$restored = unserialize(serialize($r));
$restored->equal($r);  // true
```

**Throws:**
- `DomainException` if the data is missing "numerator"/"denominator", or either value is not an integer
- `DivisionByZeroError` if the denominator is zero
- `UnderflowException` if the value is non-zero but too small to represent as a Rational
- `OverflowException` if the value is too large to represent as a Rational

### jsonSerialize()

```php
public function jsonSerialize(): array
```

Provides `Rational`'s representation for `json_encode()`, via the `JsonSerializable` interface.

**Example:**
```php
$r = new Rational(3, 4);
echo json_encode($r);  // '{"numerator":3,"denominator":4}'
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

// Mixed operations
$r1 = new Rational(1, 2);
$r2 = $r1->add(0.25);    // 1/2 + 1/4
echo $r2;                // "3/4"

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
$r = new Rational($f);
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
