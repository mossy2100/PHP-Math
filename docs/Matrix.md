# Matrix

Mutable class representing a two-dimensional matrix of numbers with comprehensive linear algebra operations.

---

## Overview

The `Matrix` class provides a complete implementation of matrix arithmetic with support for:

- Basic arithmetic (addition, subtraction, scalar/matrix multiplication, division, Hadamard product)
- Matrix-vector multiplication using column vector convention
- Matrix power with binary exponentiation (including negative powers via inverse)
- Transpose, determinant, and inverse operations
- Sub-matrix extraction, in-place pasting, and resizing
- Element and row/column access with bounds checking
- String representation using box-drawing characters
- Support for n⨉0 and 0⨉n empty matrices

Matrix data is stored privately to prevent non-rectangular or non-numeric mutation.

---

## Properties

### rowCount

```php
private(set) int $rowCount
```

The number of rows in the matrix. Read-only from outside the class. Stored explicitly (not derived from the data array)
to support 0-column matrices where the row count cannot be inferred from data.

### columnCount

```php
private(set) int $columnCount
```

The number of columns in the matrix. Read-only from outside the class. Stored explicitly for the same reason as
`rowCount`, to support 0-row matrices.

---

## Constructor

### \_\_construct()

```php
public function __construct(int $rowCount, int $columnCount)
```

Create a new zero-filled matrix with the specified dimensions.

**Parameters:**

- `$rowCount` (int) - Number of rows (must be non-negative)
- `$columnCount` (int) - Number of columns (must be non-negative)

**Throws:** `DomainException` if either dimension is negative.

**Examples:**

```php
$m1 = new Matrix(3, 3);     // 3x3 zero matrix
$m2 = new Matrix(2, 4);     // 2x4 zero matrix
$m3 = new Matrix(0, 0);     // 0x0 empty matrix
$m4 = new Matrix(0, 3);     // 0x3 empty matrix
```

---

## Factory Methods

### fromArray()

```php
public static function fromArray(array $arr): self
```

Create a matrix from a 2D array. The outer array and every row must be a list (sequential integer keys starting at 0); a
non-sequential array is rejected rather than silently re-indexed. All rows must have the same number of elements, and
every element must be numeric. Integer values are cast to float.

**Parameters:**

- `$arr` (array<array-key, mixed>) - Rectangular list of rows of numbers.

**Returns:** `self` - New matrix populated with the provided data.

**Throws:**

- `DomainException` if the outer array or any row is not a list, or any element is not a number.
- `LengthException` if the rows don't all have the same number of columns.

**Examples:**

```php
$m = Matrix::fromArray([
    [1, 2, 3],
    [4, 5, 6],
]);
// 2x3 matrix

$empty = Matrix::fromArray([]);
// 0x0 matrix
```

### identity()

```php
public static function identity(int $size): self
```

Create an identity matrix of the specified size. The identity matrix has 1s on the main diagonal and 0s elsewhere.

**Parameters:**

- `$size` (int) - Size of the identity matrix (both rows and columns)

**Returns:** `self` - Identity matrix.

**Throws:** `DomainException` if `$size` is negative.

**Examples:**

```php
$i3 = Matrix::identity(3);
// ┌               ┐
// │ 1.0  0.0  0.0 │
// │ 0.0  1.0  0.0 │
// │ 0.0  0.0  1.0 │
// └               ┘
```

---

## Conversion Methods

### toArray()

```php
public function toArray(): array
```

Get a copy of the matrix data as a rectangular array.

**Returns:** `list<list<float>>` - Rectangular array of matrix elements.

**Examples:**

```php
$m = Matrix::fromArray([[1, 2], [3, 4]]);
$arr = $m->toArray();  // [[1, 2], [3, 4]]
```

### \_\_toString()

```php
public function __toString(): string
```

Convert the matrix to a string representation using box-drawing characters. Values are right-aligned within columns.

**Examples:**

```php
$m = Matrix::fromArray([[1, 2], [3, 4]]);
echo $m;
// ┌          ┐
// │ 1.0  2.0 │
// │ 3.0  4.0 │
// └          ┘

// Empty matrices
$e = new Matrix(0, 0);
echo $e;
// ┌ ┐
// └ ┘
```

---

## Inspection Methods

### isSquare()

```php
public function isSquare(?int $size = null): bool
```

Check if the matrix is square, optionally of a specific size.

**Parameters:**

- `$size` (int|null) - If specified, check for exact size; otherwise any square matrix returns true.

**Returns:** `bool` - True if the matrix is square (and of the specified size, if given).

**Examples:**

```php
$m = Matrix::identity(3);
var_dump($m->isSquare());     // true
var_dump($m->isSquare(3));    // true
var_dump($m->isSquare(2));    // false

$m2 = new Matrix(2, 3);
var_dump($m2->isSquare());    // false
```

### get()

```php
public function get(int $row, int $col): float
```

Get a matrix element by row and column index.

**Parameters:**

- `$row` (int) - Row index (0-based).
- `$col` (int) - Column index (0-based).

**Returns:** `float` - The value at the specified position.

**Throws:** `OutOfRangeException` if either index is outside the valid range.

**Examples:**

```php
$m = Matrix::fromArray([[1, 2], [3, 4]]);
echo $m->get(0, 0);  // 1
echo $m->get(1, 1);  // 4
```

### getRow()

```php
public function getRow(int $row): Vector
```

Get a row as a Vector.

**Returns an independent copy** - mutating the returned Vector never affects this Matrix. For a live, mutable view of
a row that stays linked to the Matrix, use [`$m[$row]`](#arrayaccess-methods) instead.

**Parameters:**

- `$row` (int) - Row index (0-based)

**Returns:** `Vector` - An independent copy of the row.

**Throws:** `OutOfRangeException` if row index is outside the valid range.

**Examples:**

```php
$m = Matrix::fromArray([[1, 2, 3], [4, 5, 6]]);
$row = $m->getRow(0);  // Vector(1, 2, 3)
```

### getColumn()

```php
public function getColumn(int $col): Vector
```

Get a column as a Vector.

**Parameters:**

- `$col` (int) - Column index (0-based)

**Returns:** `Vector` - The column as a Vector.

**Throws:** `OutOfRangeException` if column index is outside the valid range.

**Examples:**

```php
$m = Matrix::fromArray([[1, 2], [3, 4], [5, 6]]);
$col = $m->getColumn(0);  // Vector(1, 3, 5)
```

### copy()

```php
public function copy(int $row, int $col, int $rowCount, int $columnCount): self
```

Extract a rectangular sub-matrix: a copy of a subset of this matrix's elements. Purely functional — does not modify this
matrix.

**Parameters:**

- `$row` (int) - Row of the top-left corner of the region to copy (0-based).
- `$col` (int) - Column of the top-left corner of the region to copy (0-based).
- `$rowCount` (int) - Number of rows to copy.
- `$columnCount` (int) - Number of columns to copy.

**Returns:** `self` - A new matrix containing the copied elements.

**Throws:** `OutOfRangeException` if either count is negative, or the selected region extends beyond this matrix's
bounds.

**Examples:**

```php
$m = Matrix::fromArray([
    [1, 2, 3],
    [4, 5, 6],
    [7, 8, 9],
]);
$sub = $m->copy(1, 1, 2, 2);
// [[5, 6],
//  [8, 9]]
```

---

## Modification Methods

### set()

```php
public function set(int $row, int $col, float $value): void
```

Set a matrix element by row and column index.

**Parameters:**

- `$row` (int) - Row index (0-based)
- `$col` (int) - Column index (0-based)
- `$value` (float) - Value to set

**Throws:**

- `OutOfRangeException` if either index is outside the valid range.
- `DomainException` if the value is not finite (±INF or NAN).

**Examples:**

```php
$m = new Matrix(2, 2);
$m->set(0, 0, 5);
$m->set(1, 1, 10);
```

### setRow()

```php
public function setRow(int $row, Vector $vec): void
```

Set a row from a Vector. `$vec`'s elements are copied into the row's existing Vector, which is never replaced - the
row's object identity is preserved, so a live reference obtained via [`$m[$row]`](#arrayaccess-methods) stays valid
and reflects the new values. `$vec` itself is never stored by reference: mutating it afterward has no effect on this
Matrix.

**Parameters:**

- `$row` (int) - Row index (0-based)
- `$vec` (Vector) - The row Vector

**Throws:**

- `OutOfRangeException` if row index is outside the valid range.
- `LengthException` if the Vector has the wrong number of elements.

**Examples:**

```php
$m = Matrix::fromArray([[1, 2, 3], [4, 5, 6]]);
$m->setRow(1, Vector::fromArray([10, 11, 12]));
```

### setColumn()

```php
public function setColumn(int $col, Vector $vec): void
```

Set a column from a Vector.

**Parameters:**

- `$col` (int) - Column index (0-based)
- `$vec` (Vector) - The column Vector

**Throws:**

- `OutOfRangeException` if column index is outside the valid range.
- `LengthException` if the Vector has the wrong number of elements.

**Examples:**

```php
$m = Matrix::fromArray([[1, 2, 3], [4, 5, 6]]);
$m->setColumn(1, Vector::fromArray([20, 50]));
// Matrix is now [[1, 20, 3], [4, 50, 6]]
```

### paste()

```php
public function paste(self $other, int $row = 0, int $col = 0): void
```

Copy the elements of another matrix into this one, starting at the given position. Unlike most methods in this class,
this one mutates the matrix in place, matching `set()`, `setRow()`, and `setColumn()`.

**Parameters:**

- `$other` (self) - The matrix to paste. Must fit within this matrix at the given offset.
- `$row` (int) - Row at which to place the top-left corner of `$other` (0-based). Defaults to 0.
- `$col` (int) - Column at which to place the top-left corner of `$other` (0-based). Defaults to 0.

**Throws:** `OutOfRangeException` if either offset is negative, or `$other` doesn't fit within this matrix at that
offset.

**Examples:**

```php
$m = new Matrix(3, 3);
$m->paste(Matrix::fromArray([[1, 2], [3, 4]]), 1, 1);
// [[0, 0, 0],
//  [0, 1, 2],
//  [0, 3, 4]]
```

---

## Comparison Methods

The `equal()` and `approxEqual()` methods are provided by the
[`ApproxEquatable`](https://github.com/mossy2100/PHP-Core/blob/main/docs/Traits/Comparison/ApproxEquatable.md) trait
from the [Core](https://github.com/mossy2100/PHP-Core) package, with `Matrix` supplying its own type-checking logic
since the trait's parameter is typed `mixed` (see the trait's docs for why).

Both methods accept only a `Matrix` for `$other` — not a `Vector` or a plain array, even though those could plausibly
represent the same values. Anything else throws `InvalidArgumentException` rather than silently returning `false`, to
catch bugs from comparing values that can't meaningfully be compared.

### equal()

```php
public function equal(mixed $other): bool
```

Check if this matrix exactly equals another value.

Two matrices are equal if they have the same dimensions and all corresponding elements are exactly equal.

**Parameters:**

- `$other` (mixed) - The value to compare with (must be a `Matrix`).

**Returns:**

- `bool` - True if the matrices have the same dimensions and all elements are exactly equal.

**Throws:** `InvalidArgumentException` if `$other` is not a `Matrix`.

**Examples:**

```php
$m1 = Matrix::fromArray([[1, 2], [3, 4]]);
$m2 = Matrix::fromArray([[1, 2], [3, 4]]);
$m3 = Matrix::fromArray([[1.0000000001, 2], [3, 4]]);

var_dump($m1->equal($m2));  // true (exact match)
var_dump($m1->equal($m3));  // false (not exact)

// Anything else throws, rather than silently returning false
$m1->equal([[1, 2], [3, 4]]);  // throws InvalidArgumentException
$m1->equal('string');          // throws InvalidArgumentException
$m1->equal(null);              // throws InvalidArgumentException
```

### approxEqual()

```php
public function approxEqual(
    mixed $other,
    float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
    float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
): bool
```

Check if this matrix approximately equals another value within specified tolerances.

Each pair of corresponding elements is compared using `Floats::approxEqual()`, which checks absolute tolerance first,
then relative tolerance.

**Parameters:**

- `$other` (mixed) - The value to compare with (must be a `Matrix`).
- `$relTol` (float) - Relative tolerance (default: 1e-9).
- `$absTol` (float) - Absolute tolerance (default: PHP_FLOAT_EPSILON).

**Returns:**

- `bool` - True if the matrices have the same dimensions and all elements are approximately equal.

**Throws:**

- `InvalidArgumentException` if `$other` is not a `Matrix`.
- `DomainException` if either tolerance is negative.

**@see** `Floats::approxEqual()`

**Examples:**

```php
$m1 = Matrix::fromArray([[1, 2], [3, 4]]);
$m2 = Matrix::fromArray([[1.00000001, 2.00000001], [3.00000001, 4.00000001]]);

// Within default tolerance
var_dump($m1->approxEqual($m2));  // true

// With tight tolerance
var_dump($m1->approxEqual($m2, 1e-15, 1e-15));  // false

// Anything else throws, rather than silently returning false
$m1->approxEqual('string');  // throws InvalidArgumentException
```

---

## Transformation Methods

### resize()

```php
public function resize(int $rowCount, int $columnCount): self
```

Create a new matrix with the given dimensions, containing as much of this matrix's data as fits. The result is anchored
at (0, 0): if the new dimensions are larger than this matrix's, the extra rows and/or columns are zero-filled; if
smaller, the excess rows/columns (from the bottom and/or right) are dropped. To resize from a different corner, or to
insert/remove a row or column at an arbitrary position, compose `copy()` and `paste()` directly instead.

**Parameters:**

- `$rowCount` (int) - The number of rows in the resized matrix.
- `$columnCount` (int) - The number of columns in the resized matrix.

**Returns:** `self` - A new matrix with the given dimensions.

**Throws:** `DomainException` if either dimension is negative.

**Examples:**

```php
$m = Matrix::fromArray([[1, 2], [3, 4]]);

// Grow: extra rows/columns are zero-filled
$grown = $m->resize(3, 3);
// [[1, 2, 0],
//  [3, 4, 0],
//  [0, 0, 0]]

// Shrink: excess rows/columns are dropped
$shrunk = $m->resize(1, 1);
// [[1]]
```

---

## Unary Arithmetic Methods

### neg()

```php
public function neg(): self
```

Negate this matrix. Returns a new matrix with all elements negated.

**Returns:** `self` - A new matrix with all elements negated.

**Example:**

```php
$m = Matrix::fromArray([[1, -2], [3, -4]]);
$result = $m->neg();
// [[-1, 2], [-3, 4]]
```

### inv()

```php
public function inv(): self
```

Calculate the inverse of this matrix. Uses cofactor expansion with the adjugate matrix. The matrix must be square and
invertible (non-zero determinant).

**Note:** The underlying algorithm has O(n! × n²) time complexity. It is suitable for small matrices (up to ~10×10) but
will be extremely slow for larger ones.

**Returns:** `self` - New matrix representing the inverse.

**Throws:**

- `DomainException` if the matrix is not square.
- `ArithmeticException` if the matrix is not invertible (determinant is zero).

**Examples:**

```php
$m = Matrix::fromArray([[1, 2], [3, 4]]);
$inv = $m->inv();

// Verify: M × M⁻¹ = I
$identity = $m->mul($inv);
```

---

## Binary Arithmetic Methods

### add()

```php
public function add(self $other): self
```

Add another matrix to this one. Both matrices must have the same dimensions.

**Parameters:**

- `$other` (self) - Matrix to add

**Returns:** `self` - New matrix representing the sum.

**Throws:** `LengthException` if matrices have different dimensions.

**Examples:**

```php
$a = Matrix::fromArray([[1, 2], [3, 4]]);
$b = Matrix::fromArray([[5, 6], [7, 8]]);
$sum = $a->add($b);
// [[6, 8], [10, 12]]
```

### sub()

```php
public function sub(self $other): self
```

Subtract another matrix from this one. Both matrices must have the same dimensions.

**Parameters:**

- `$other` (self) - Matrix to subtract

**Returns:** `self` - New matrix representing the difference.

**Throws:** `LengthException` if matrices have different dimensions.

**Examples:**

```php
$a = Matrix::fromArray([[5, 6], [7, 8]]);
$b = Matrix::fromArray([[1, 2], [3, 4]]);
$diff = $a->sub($b);
// [[4, 4], [4, 4]]
```

### mul()

```php
public function mul(float|self $other): self
```

Multiply this matrix by a scalar or another matrix.

When multiplying by a scalar, each element is scaled. When multiplying by a matrix, standard matrix multiplication is
performed (the number of columns in this matrix must equal the number of rows in the other).

To multiply by a `Vector` (_Ax_), use [`mulVector()`](#mulvector) instead.

**Parameters:**

- `$other` (float|self) - Number or matrix to multiply by

**Returns:** `self` - A new matrix representing the product.

**Throws:** `LengthException` if dimensions are incompatible for matrix multiplication.

**Examples:**

```php
$m = Matrix::fromArray([[1, 2], [3, 4]]);

// Scalar multiplication
$scaled = $m->mul(2);
// [[2, 4], [6, 8]]

// Matrix multiplication
$m2 = Matrix::fromArray([[5, 6], [7, 8]]);
$product = $m->mul($m2);
// [[19, 22], [43, 50]]
```

### div()

```php
public function div(float $scalar): self
```

Divide this matrix by a scalar.

**Parameters:**

- `$scalar` (float) - Number to divide by.

**Returns:** `self` - New matrix representing the quotient.

**Throws:** `ArithmeticException` if `$scalar` is zero.

**Examples:**

```php
$m = Matrix::fromArray([[2, 4], [6, 8]]);
$result = $m->div(2);
// [[1, 2], [3, 4]]
```

### hadamard()

```php
public function hadamard(self $other): self
```

Calculate the Hadamard product (element-wise product) of this matrix with another. Both matrices must have the same
dimensions.

**Parameters:**

- `$other` (self) - Matrix to multiply element-wise with.

**Returns:** `self` - New matrix representing the Hadamard product.

**Throws:** `LengthException` if matrices have different dimensions.

**Examples:**

```php
$a = Matrix::fromArray([[1, 2], [3, 4]]);
$b = Matrix::fromArray([[5, 6], [7, 8]]);
$result = $a->hadamard($b);
// [[5, 12], [21, 32]]
```

---

## Power Methods

### pow()

```php
public function pow(int $exponent): self
```

Raise this matrix to an integer power. Uses binary exponentiation for efficiency. Negative powers use the matrix
inverse. The matrix must be square.

**Note:** `pow(1)` returns a new instance (a clone), not `$this` — since `Matrix` is mutable, mutating the result must
never affect the original.

**Parameters:**

- `$exponent` (int) - Power to raise to

**Returns:** `self` - New matrix representing the result.

**Throws:**

- `DomainException` if the matrix is not square.
- `ArithmeticException` if the matrix is not invertible (zero determinant) for negative powers.

**Examples:**

```php
$m = Matrix::fromArray([[1, 1], [0, 1]]);

$m0 = $m->pow(0);   // Identity matrix
$m2 = $m->pow(2);   // [[1, 2], [0, 1]]
$m3 = $m->pow(3);   // [[1, 3], [0, 1]]
$mi = $m->pow(-1);  // Inverse matrix
```

### sqr()

```php
public function sqr(): self
```

Square this matrix. Equivalent to `pow(2)`, but more efficient and readable. The matrix must be square.

**Returns:** `self` - New matrix representing the square.

**Throws:** `DomainException` if the matrix is not square.

**Example:**

```php
$m = Matrix::fromArray([[1, 2], [3, 4]]);
$result = $m->sqr();  // [[7, 10], [15, 22]]
```

---

## Linear Algebra Methods

### mulVector()

```php
public function mulVector(Vector $vector): Vector
```

Multiply this matrix by a vector (_Ax_). The vector is treated as a column vector; its size must equal this matrix's
column count.

To go the other way (_xA_), use `Vector::mul()` instead.

**Parameters:**

- `$vector` (Vector) - The vector to multiply by.

**Returns:** `Vector` - New vector representing the result.

**Throws:** `LengthException` if the vector's size doesn't equal this matrix's column count.

**Examples:**

```php
$m = Matrix::fromArray([[1, 2], [3, 4]]);
$v = Vector::fromArray([1, 2]);
$result = $m->mulVector($v);  // Vector(5, 11)
```

### t()

```php
public function t(): self
```

Get the transpose of this matrix. Rows become columns and columns become rows.

**Returns:** `self` - New matrix representing the transpose.

**Examples:**

```php
$m = Matrix::fromArray([[1, 2, 3], [4, 5, 6]]);
$t = $m->t();
// [[1, 4], [2, 5], [3, 6]]
```

### det()

```php
public function det(): float
```

Calculate the determinant of this matrix using recursive cofactor expansion. The matrix must be square.

**Returns:** `float` - The determinant.

**Throws:** `DomainException` if the matrix is not square.

**Examples:**

```php
$m = Matrix::fromArray([[1, 2], [3, 4]]);
echo $m->det();  // -2.0

$i = Matrix::identity(3);
echo $i->det();  // 1.0
```

### trace()

```php
public function trace(): float
```

Calculate the trace of this matrix (sum of diagonal elements). The matrix must be square.

**Returns:** `float` - The trace.

**Throws:** `DomainException` if the matrix is not square.

**Examples:**

```php
$m = Matrix::fromArray([[1, 2], [3, 4]]);
echo $m->trace();  // 5.0

$i = Matrix::identity(3);
echo $i->trace();  // 3.0
```

---

## Norm Methods

### norm()

```php
public function norm(): float
```

Calculate the Frobenius norm (square root of the sum of all squared elements). This is the matrix analogue of the
Euclidean norm for vectors.

**Returns:** `float` - The Frobenius norm.

**Examples:**

```php
$m = Matrix::fromArray([[1, 2], [3, 4]]);
echo $m->norm();  // 5.477... (sqrt(30))

$i = Matrix::identity(3);
echo $i->norm();  // 1.732... (sqrt(3))
```

### p1Norm()

```php
public function p1Norm(): float
```

Calculate the P1 norm (maximum absolute column sum).

**Returns:** `float` - The P1 norm.

**Examples:**

```php
$m = Matrix::fromArray([[1, -2], [3, 4]]);
echo $m->p1Norm();  // 6.0 (max of |1|+|3|=4, |-2|+|4|=6)
```

### pInfNorm()

```php
public function pInfNorm(): float
```

Calculate the P-infinity norm (maximum absolute row sum).

**Returns:** `float` - The P-infinity norm.

**Examples:**

```php
$m = Matrix::fromArray([[1, -2], [3, 4]]);
echo $m->pInfNorm();  // 7.0 (max of |1|+|-2|=3, |3|+|4|=7)
```

---

## Aggregation Methods

### count()

```php
public function count(): int
```

Get the total number of elements in the matrix (`rowCount * columnCount`), via the `Countable` interface.

**Examples:**

```php
$m = new Matrix(2, 3);
echo $m->count();  // 6
echo count($m);    // 6 (via the global count() function)
```

---

## ArrayAccess Methods

Matrices can be accessed using bracket syntax, including chained double-index access:

```php
$m = Matrix::fromArray([[1, 2, 3], [4, 5, 6]]);

// Read access
echo $m[0][0];  // 1
echo $m[1][2];  // 6

// Write access
$m[0][1] = 20;
echo $m[0][1];  // 20

// Set a whole row
$m[1] = Vector::fromArray([40, 50, 60]);

// Check existence
var_dump(isset($m[0]));  // true
var_dump(isset($m[5]));  // false

// Cannot unset rows
unset($m[0]);  // Throws LogicException
```

**`$m[$row]` returns the Matrix's actual internal row `Vector`, not a copy.** Mutating it mutates the Matrix. This is
what makes `$m[$row][$col] = $x` work: PHP fetches the row via `offsetGet()`, then sets the element on that same
`Vector` object.

This is different from [`getRow()`](#getrow), which returns an **independent copy** - mutating the result of
`getRow()` never affects the Matrix. See `getRow()` and `setRow()` below for the full contrast.

### offsetExists()

```php
public function offsetExists(mixed $offset): bool
```

Check if a row offset exists. Returns true if the offset is an integer within the valid range.

**Parameters:**
- `$offset` (mixed) - Row index to check.

**Returns:**
- `bool` - True if the offset is valid.

### offsetGet()

```php
public function offsetGet(mixed $offset): Vector
```

Get the row `Vector` at an offset. This is the Matrix's live internal row - see the note above.

**Parameters:**
- `$offset` (mixed) - Row index to get.

**Returns:**
- `Vector` - The live row Vector.

**Throws:**
- `InvalidArgumentException` if the offset is not an int.
- `OutOfRangeException` if the offset is outside the valid range.

### offsetSet()

```php
public function offsetSet(mixed $offset, mixed $value): void
```

Set a row from a `Vector`. Equivalent to `setRow()`: the given Vector's elements are copied into the row's existing
Vector, which is never replaced.

**Parameters:**
- `$offset` (mixed) - Row index to set.
- `$value` (mixed) - The row Vector.

**Throws:**
- `InvalidArgumentException` if the offset is not an int, or the value is not a `Vector`.
- `OutOfRangeException` if the offset is outside the valid range.
- `LengthException` if the Vector has the wrong number of elements.

### offsetUnset()

```php
public function offsetUnset(mixed $offset): void
```

Unsetting rows is not supported.

**Throws:**
- `LogicException` - Always throws.

---

## Usage Examples

### Building a Matrix

```php
// From constructor + set
$m = new Matrix(3, 3);
$m->set(0, 0, 1);
$m->set(1, 1, 1);
$m->set(2, 2, 1);

// From array
$m = Matrix::fromArray([
    [1, 2, 3],
    [4, 5, 6],
    [7, 8, 9],
]);

// Identity shorthand
$i = Matrix::identity(3);
```

### Solving a Linear System

```php
// Solve Ax = b using x = A⁻¹b
$a = Matrix::fromArray([[2, 1], [5, 3]]);
$b = Vector::fromArray([4, 7]);

$x = $a->inv()->mulVector($b);  // Vector(5, -6)
```

### 3D Transformations

```php
// Rotate a point 90° around the Z-axis.
// The rotation matrix for angle θ around Z is:
//   [ cos θ  -sin θ  0 ]
//   [ sin θ   cos θ  0 ]
//   [   0       0    1 ]
$rot90 = Matrix::fromArray([
    [0, -1, 0],
    [1,  0, 0],
    [0,  0, 1],
]);

$point = Vector::fromArray([1, 0, 0]);
$rotated = $rot90->mulVector($point);  // Vector(0, 1, 0)

// Scale by 2x in all axes.
$scale = Matrix::fromArray([
    [2, 0, 0],
    [0, 2, 0],
    [0, 0, 2],
]);
$scaled = $scale->mulVector($point);  // Vector(2, 0, 0)

// Chain transformations: scale then rotate.
$combined = $rot90->mul($scale);
$result = $combined->mulVector($point);  // Vector(0, 2, 0)
```

### Matrix Powers

```php
// Fibonacci via matrix exponentiation
$fib = Matrix::fromArray([[1, 1], [1, 0]]);
$f10 = $fib->pow(10);
echo $f10->get(0, 0);  // 89 (the 10th Fibonacci number)
```

### Embedding a Sub-Matrix (copy/paste)

```php
// Embed a 2x2 rotation into the top-left of a 3x3 homogeneous transform.
$rotation = Matrix::fromArray([
    [0, -1],
    [1,  0],
]);

$transform = Matrix::identity(3);
$transform->paste($rotation);
// [[0, -1, 0],
//  [1,  0, 0],
//  [0,  0, 1]]

// Extract it back out.
$extracted = $transform->copy(0, 0, 2, 2);
$rotation->equal($extracted);  // true
```

---

## See Also

- **[Vector](Vector.md)** - Numeric vectors, used as rows/columns and for matrix-vector multiplication
- **[Complex](Complex.md)** - Complex number arithmetic
- **[Rational](Rational.md)** - Exact rational number arithmetic
