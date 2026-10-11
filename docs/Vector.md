# Vector

Numeric vector with element-wise arithmetic, dot/cross/Hadamard products, aggregation, and array-style access.

---

## Overview

The `Vector` class provides a general-purpose numeric vector with support for:

- Element-wise arithmetic (addition, subtraction, scalar multiplication, scalar division, Hadamard product)
- Dot product, cross product, and normalization
- Aggregation (sum, product)
- Exact and approximate equality comparison
- Conversion to arrays and to row/column matrices
- Array-style element access via the `ArrayAccess` interface, and element counting via `Countable`
- Iteration over the elements with `foreach`, via `IteratorAggregate`

Vectors are directionless (neither row nor column). When converted to a `Matrix`, use `toColumnMatrix()` or
`toRowMatrix()` to pick the orientation explicitly.

Vectors with a count of 0 are allowed.

---

## Properties

### $magnitude

```php
public float $magnitude { get; }
```

The magnitude (Euclidean norm) of the vector. Computed on demand from the current elements every time it's read. It
isn't cached, so it's always up to date even after mutating the vector via `set()`.

For v = (x₁, x₂, ..., xₙ): ‖v‖ = √(x₁² + x₂² + ... + xₙ²)

**Throws:** `OverflowException` if the magnitude is too large to represent as a float.

### $count

```php
public int $count { get; }
```

The number of elements in the vector.

---

## Constructor

### \_\_construct()

```php
public function __construct(int $count)
```

Create a new vector with the specified number of elements, all initialised to zero.

**Parameters:**

- `$count` (int) - Number of elements.

**Throws:**

- `DomainException` if count is negative.

**Examples:**

```php
$v1 = new Vector(3);    // [0.0, 0.0, 0.0]
$v2 = new Vector(0);    // [] (empty vector)
```

---

## Factory Methods

### fromArray()

```php
public static function fromArray(array $arr): self
```

Create a vector from an array of numbers. The array must be a list (sequential integer keys starting at 0); a
non-sequential array is rejected rather than silently re-indexed. Every element must be a finite number, so `INF`,
`-INF`, and `NAN` are rejected. Integer values are cast to float.

**Parameters:**

- `$arr` (array<array-key, mixed>) - List of numbers.

**Returns:** `self` - A new vector containing the array values.

**Throws:**

- `InvalidArgumentException` if any element isn't an `int` or `float`.
- `DomainException` if the array is not a list, or any element is not finite (±INF or NAN).

**Examples:**

```php
$v1 = Vector::fromArray([1, 2, 3]);
$v2 = Vector::fromArray([3.14, -1, 0]);
$v3 = Vector::fromArray([]);  // Count-0 vector

// Non-list arrays are rejected, not re-indexed.
Vector::fromArray([5 => 10, 10 => 20]);  // throws DomainException

// Non-numeric and non-finite elements are rejected too.
Vector::fromArray([1, '2']);  // throws InvalidArgumentException
Vector::fromArray([1, INF]);  // throws DomainException
```

---

## Conversion Methods

### toArray()

```php
public function toArray(): array
```

Get a copy of the vector data as an array.

**Returns:** `list<float>` - Array of vector elements.

**Examples:**

```php
$v = Vector::fromArray([1, 2, 3]);
$array = $v->toArray();  // [1.0, 2.0, 3.0]
```

### toRowMatrix()

```php
public function toRowMatrix(): Matrix
```

Convert the vector to a single-row (1×n) Matrix.

**Examples:**

```php
$v = Vector::fromArray([1, 2, 3]);
$row = $v->toRowMatrix();
// [[1, 2, 3]]  (1 row, 3 columns)
```

### toColumnMatrix()

```php
public function toColumnMatrix(): Matrix
```

Convert the vector to a single-column (n×1) Matrix.

**Examples:**

```php
$v = Vector::fromArray([1, 2, 3]);
$col = $v->toColumnMatrix();
// [[1],
//  [2],
//  [3]]  (3 rows, 1 column)
```

### \_\_toString()

```php
public function __toString(): string
```

Convert the vector to a string representation, using ordered tuple notation and mathematical angle brackets.

**Examples:**

```php
$v = Vector::fromArray([1, 2, 3]);
echo $v;  // "⟨1, 2, 3⟩"
```

---

## Inspection Methods

### get()

```php
public function get(int $index): float
```

Get a vector element by index.

**Parameters:**

- `$index` (int) - Element index (0-based).

**Returns:** `float` - Value of the vector element.

**Throws:** `OutOfRangeException` if the index is outside the valid range.

**Examples:**

```php
$v = Vector::fromArray([10, 20, 30]);
echo $v->get(0);  // 10.0
echo $v->get(2);  // 30.0
```

---

## Modification Methods

### set()

```php
public function set(int $index, float $value): void
```

Set a vector element by index. Integer values are cast to float.

**Parameters:**

- `$index` (int) - Element index (0-based).
- `$value` (float) - Value to set.

**Throws:**

- `OutOfRangeException` if the index is outside the valid range.
- `DomainException` if the value is not finite (±INF or NAN).

**Examples:**

```php
$v = Vector::fromArray([1, 2, 3]);
$v->set(1, 99);
echo $v->get(1);  // 99.0
```

---

## Comparison Methods

The `equal()` and `approxEqual()` methods are provided by the [`ApproxEquatable`](https://github.com/mossy2100/PHP-Core/blob/main/docs/Traits/Comparison/ApproxEquatable.md) trait from the [Core](https://github.com/mossy2100/PHP-Core) package, with
`Vector` supplying its own type-checking logic since the trait's parameter is typed `mixed` (see the trait's docs for
why).

Both methods accept only a `Vector` for `$other` - not an `array` or a single-column `Matrix`, even though those could
plausibly represent the same values. Anything else throws `InvalidArgumentException` rather than silently returning
`false`, to catch bugs from comparing values that can't meaningfully be compared.

### equal()

```php
public function equal(mixed $other): bool
```

Check if the vector exactly equals another value.

Two vectors are equal if they have the same count and all corresponding elements are exactly equal.

**Parameters:**

- `$other` (mixed) - The value to compare with (must be a `Vector`).

**Returns:** `bool` - True if the vectors are the same count and all elements are exactly equal.

**Throws:** `InvalidArgumentException` if `$other` is not a `Vector`.

**Examples:**

```php
$v1 = Vector::fromArray([1, 2, 3]);
$v2 = Vector::fromArray([1, 2, 3]);
$v3 = Vector::fromArray([1.0000000001, 2, 3]);

var_dump($v1->equal($v2));  // true (exact match)
var_dump($v1->equal($v3));  // false (not exact)

// Anything else throws, rather than silently returning false
$v1->equal([1, 2, 3]);  // throws InvalidArgumentException
$v1->equal('string');   // throws InvalidArgumentException
$v1->equal(null);       // throws InvalidArgumentException
```

### approxEqual()

```php
public function approxEqual(
    mixed $other,
    float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
    float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
): bool
```

Check if the vector approximately equals another value within specified tolerances.

Each pair of corresponding elements is compared using `Floats::approxEqual()`, which checks absolute tolerance first,
then relative tolerance.

**Parameters:**

- `$other` (mixed) - The value to compare with (must be a `Vector`).
- `$relTol` (float) - Relative tolerance (default: 1e-9).
- `$absTol` (float) - Absolute tolerance (default: PHP_FLOAT_EPSILON).

**Returns:** `bool` - True if the vectors are the same count and all elements are approximately equal.

**Throws:**

- `InvalidArgumentException` if `$other` is not a `Vector`.
- `DomainException` if either tolerance is negative.

**Examples:**

```php
$v1 = Vector::fromArray([1, 2, 3]);
$v2 = Vector::fromArray([1.00000001, 2.00000001, 3.00000001]);

// Within default tolerance
var_dump($v1->approxEqual($v2));  // true

// With tight tolerance
var_dump($v1->approxEqual($v2, 1e-15, 1e-15));  // false

// Anything else throws, rather than silently returning false
$v1->approxEqual('string');  // throws InvalidArgumentException
```

### Comparison Operators

See: [Comparison Operators](Comparison_Operators.md) to read more about how the `==`, `<`, etc. operators work for the types in this package.

---

## Unary Arithmetic Methods

### neg()

```php
public function neg(): self
```

Negate the vector. Returns a new vector with all elements negated.

**Example:**

```php
$v = Vector::fromArray([1, -2, 3]);
$result = $v->neg();  // [-1, 2, -3]
```

### reciprocal()

```php
public function reciprocal(): self
```

Calculate the element-wise reciprocal of the vector.

**Returns:** `self` - New vector with each element replaced by its reciprocal.

**Throws:**

- `ArithmeticException` if any element is zero.
- `OverflowException` if the result is too large to represent as a float.

**Example:**

```php
$v = Vector::fromArray([2, 4, 5]);
$result = $v->reciprocal();  // [0.5, 0.25, 0.2]
```

---

## Binary Arithmetic Methods

### add()

```php
public function add(self $other): self
```

Add one vector to another, element by element.

**Parameters:**

- `$other` (Vector) - Vector to add.

**Returns:** `self` - New vector representing the sum.

**Throws:**

- `LengthException` if vectors have different counts.
- `OverflowException` if the result is too large to represent as a float.

**Examples:**

```php
$v1 = Vector::fromArray([1, 2, 3]);
$v2 = Vector::fromArray([4, 5, 6]);
$sum = $v1->add($v2);  // [5, 7, 9]
```

### sub()

```php
public function sub(self $other): self
```

Subtract one vector from another, element by element.

**Parameters:**

- `$other` (Vector) - Vector to subtract.

**Returns:** `self` - New vector representing the difference.

**Throws:**

- `LengthException` if vectors have different counts.
- `OverflowException` if the result is too large to represent as a float.

**Examples:**

```php
$v1 = Vector::fromArray([5, 7, 9]);
$v2 = Vector::fromArray([1, 2, 3]);
$diff = $v1->sub($v2);  // [4, 5, 6]
```

### mul()

```php
public function mul(float|self|Matrix $other): self
```

Multiply the vector by a scalar, another vector, or a matrix.

- Multiplying by a vector is element-wise (the Hadamard product). The vectors must have the same count.
- Multiplying by a matrix (_xA_) treats the vector as a row vector. Its count must equal the matrix's row count.

To multiply a matrix by a vector in order to get a new vector, call [Matrix::mul()](Matrix.md#mul).

**Parameters:**

- `$other` (float|Vector|Matrix) - Number, vector, or matrix to multiply by.

**Returns:** `self` - New vector representing the product.

**Throws:**

- `LengthException` if the vectors have different counts, or the matrix's row count doesn't equal the vector's count.
- `DomainException` if `$other` is a float that isn't finite (±INF or NAN).
- `OverflowException` if the result is too large to represent as a float.

**Examples:**

```php
$v = Vector::fromArray([1, 2, 3]);
$result = $v->mul(3);  // [3, 6, 9]

$v2 = Vector::fromArray([4, 5, 6]);
$result = $v->mul($v2);  // [4, 10, 18]

$m = Matrix::fromArray([
    [1, 4],
    [2, 5],
    [3, 6],
]);
$result = $v->mul($m);  // [14, 32]  (1*1+2*2+3*3, 1*4+2*5+3*6)
```

### div()

```php
public function div(float|self $other): self
```

Divide the vector by a scalar or another vector. Dividing by a vector is element-wise (Hadamard division). The vectors
must have the same count.

**Parameters:**

- `$other` (float|Vector) - Number or vector to divide by.

**Returns:** `self` - New vector representing the quotient.

**Throws:**

- `DomainException` if `$other` is a float that isn't finite (±INF or NAN).
- `ArithmeticException` if `$other` is zero, or a vector with a zero element.
- `LengthException` if the vectors have different counts.
- `OverflowException` if the result is too large to represent as a float.

**Examples:**

```php
$v = Vector::fromArray([6, 9, 12]);
$result = $v->div(3);  // [2, 3, 4]

$v2 = Vector::fromArray([3, 3, 4]);
$result = $v->div($v2);  // [2, 3, 3]
```

---

## Linear Algebra Methods

### dot()

```php
public function dot(self $other): float
```

Calculate the dot product of one vector with another vector.

**Parameters:**

- `$other` (Vector) - Vector to calculate dot product with.

**Returns:** `float` - The dot product.

**Throws:**

- `LengthException` if vectors have different counts.
- `OverflowException` if the result is too large to represent as a float.

**Examples:**

```php
$v1 = Vector::fromArray([1, 2, 3]);
$v2 = Vector::fromArray([4, 5, 6]);
$result = $v1->dot($v2);  // 32.0 (1*4 + 2*5 + 3*6)
```

### cross()

```php
public function cross(self $other): self
```

Calculate the cross product of one vector with another. Both vectors must have a count of 3.

**Parameters:**

- `$other` (Vector) - Vector to calculate cross product with.

**Returns:** `self` - New vector representing the cross product.

**Throws:**

- `LengthException` if either vector's count isn't 3.
- `OverflowException` if the result is too large to represent as floats.

**Examples:**

```php
$v1 = Vector::fromArray([1, 0, 0]);
$v2 = Vector::fromArray([0, 1, 0]);
$result = $v1->cross($v2);  // [0, 0, 1]
```

### outer()

```php
public function outer(self $other): Matrix
```

Calculate the outer product of one vector with another vector. Unlike `dot()`, the vectors don't need to have the same
count. The result is always an _m×n_ `Matrix`, where _m_ is the first vector's count and _n_ is the second vector's
count.

**Parameters:**

- `$other` (Vector) - Vector to calculate outer product with.

**Returns:** `Matrix` - New matrix representing the outer product.

**Throws:** `OverflowException` if the result is too large to represent as a float.

**Examples:**

```php
$v1 = Vector::fromArray([1, 2]);
$v2 = Vector::fromArray([3, 4]);
$result = $v1->outer($v2);  // [[3, 4], [6, 8]]
```

### normalize()

```php
public function normalize(): self
```

Normalize the vector to a unit vector (magnitude 1). Returns a new vector with the same direction, leaving the original
unchanged.

**Returns:** `self` - A new vector with magnitude 1.

**Throws:** `ArithmeticException` - If the vector has zero magnitude.

**Examples:**

```php
$v = Vector::fromArray([3, 4]);
$unit = $v->normalize();
echo $unit->magnitude;  // 1
echo $unit->get(0);     // 0.6
echo $unit->get(1);     // 0.8
```

---

## Aggregation Methods

### sum()

```php
public function sum(): float
```

Calculate the sum of all elements in the vector.

**Returns:** `float` - The sum. `0.0` for an empty vector (the additive identity).

**Throws:** `OverflowException` if the result is too large to represent as a float.

**Examples:**

```php
$v = Vector::fromArray([1, 2, 3, 4]);
echo $v->sum();  // 10.0
```

### prod()

```php
public function prod(): float
```

Calculate the product of all elements in the vector.

**Returns:** `float` - The product. `1.0` for an empty vector (the multiplicative identity).

**Throws:** `OverflowException` if the result is too large to represent as a float.

**Examples:**

```php
$v = Vector::fromArray([1, 2, 3, 4]);
echo $v->prod();  // 24.0
```

### count()

```php
public function count(): int
```

Get the number of elements in the vector, via the `Countable` interface. Equivalent to the `count` property.

**Examples:**

```php
$v = Vector::fromArray([1, 2, 3]);
echo $v->count();  // 3
echo count($v);    // 3 (via the global count() function)
```

---

## ArrayAccess Methods

Vectors can be accessed using bracket syntax:

```php
$v = Vector::fromArray([1, 2, 3]);

// Read access
echo $v[0];  // 1
echo $v[1];  // 2
echo $v[2];  // 3

// Write access
$v[0] = 10;
echo $v[0];  // 10

// Check existence
var_dump(isset($v[0]));  // true
var_dump(isset($v[5]));  // false

// Cannot unset elements
unset($v[0]);  // Throws LogicException
```

### offsetExists()

```php
public function offsetExists(mixed $offset): bool
```

Check if an offset exists. Returns true if the offset is an integer within the valid range.

**Parameters:**

- `$offset` (mixed) - Index to check.

**Returns:** `bool` - True if the offset is valid.

### offsetGet()

```php
public function offsetGet(mixed $offset): float
```

Get value at an offset.

**Parameters:**

- `$offset` (mixed) - Index to get.

**Returns:** `float` - The value at the given index.

**Throws:**

- `InvalidArgumentException` if the offset is not an int.
- `OutOfRangeException` if the offset is outside the valid range.

### offsetSet()

```php
public function offsetSet(mixed $offset, mixed $value): void
```

Set value at an offset.

**Parameters:**

- `$offset` (mixed) - Index to set.
- `$value` (mixed) - Value to set.

**Throws:**

- `InvalidArgumentException` if the offset is not an int, or the value is not a number.
- `OutOfRangeException` if the offset is outside the valid range.

### offsetUnset()

```php
public function offsetUnset(mixed $offset): void
```

Unsetting elements is not supported.

**Throws:**

- `LogicException` - Always throws.

---

## IteratorAggregate Implementation

### getIterator()

```php
public function getIterator(): Traversable
```

Get an iterator over the elements, for `foreach` loops. It yields each index and element.

The elements are a snapshot: changing the Vector during the loop doesn't change the values still to come. Assigning to
the loop variable, even by reference, doesn't change the Vector either. Use `$v[$i] = ...` instead.

**Returns:** `Traversable<int, float>` - An `ArrayIterator` over the elements.

**Example:**

```php
$v = Vector::fromArray([1, 2, 3]);

foreach ($v as $i => $x) {
    echo "$i: $x\n";  // 0: 1, 1: 2, 2: 3
}

iterator_to_array($v);  // [1.0, 2.0, 3.0], the same as toArray()
```

---

## See Also

- **[Matrix](Matrix.md)** - Matrix operations, including matrix-vector multiplication
- **[Complex](Complex.md)** - Complex number arithmetic
- **[Rational](Rational.md)** - Exact rational number arithmetic
