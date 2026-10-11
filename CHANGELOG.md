# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added

- **`Rational::pow()` accepts a `Rational` exponent.** `p/q` means the q-th root, raised to the power p. The result must
  be rational, so it's exact or it throws `DomainException`:
  - `(4/9) ** (1/2)` is `2/3`, and an odd root of a negative base is real: `(-8/27) ** (1/3)` is `-2/3`.
  - An irrational result, e.g. `2 ** (1/2)`, or an even root of a negative base throws.
  - Whether there's an exact root is determined with integer arithmetic only.
- **`Rational::trunc()`** gives the integer part, truncating toward zero, like `round(RoundingMode::TowardsZero)`.

### Changed

- **`Matrix::mul()` accepts a `Vector`, replacing `Matrix::mulVector()` (breaking).** `$m->mul($v)` is _Ax_ and returns
  a `Vector`. PHPStan infers the return type from the argument type, which is what `mulVector()` existed for.
- **`Vector::mul()` and `Vector::div()` accept a `Vector`, replacing `hadamardMul()` and `hadamardDiv()` (breaking).**
  `$v1->mul($v2)` and `$v1->div($v2)` are element-wise. `div()`'s parameter is renamed from `$scalar` to `$other`.
  `Matrix::hadamardMul()` and `Matrix::hadamardDiv()` are unchanged.
- **`Vector::fromArray()`/`Matrix::fromArray()` (breaking):** a non-numeric element now throws
  `InvalidArgumentException` instead of `DomainException`, and the message no longer includes the value, e.g. "Cannot
  create Vector from array. Element at index 1 must be an int or float." A non-finite element still throws
  `DomainException`, e.g. "Cannot create Vector from array. Element at index 1 (INF) must be finite." Likewise, a
  `Matrix::fromArray()` row that isn't an array now throws `InvalidArgumentException`; a row that's an array but not a
  list still throws `DomainException`.
- **`Vector::normalize()`** throws "Cannot normalize zero Vector." for a zero or empty vector, instead of "Cannot divide
  by zero." The exception class (`ArithmeticException`) is unchanged.
- **Overflow throws `OverflowException` everywhere (breaking).** A result, or an intermediate value, too large to
  represent now throws `OverflowException`, with a message naming the operation, e.g. "Cannot multiply Complex numbers.
  The result would overflow." A `NAN` from finite operands comes from an intermediate overflow, so it's reported the
  same way. Previously:
  - `Complex` arithmetic, `pow()`, `exp()`, and the trigonometric and hyperbolic methods threw `DomainException`, e.g.
    "Cannot create Complex with non-finite real part: INF."
  - `Vector` and `Matrix` arithmetic threw `DomainException` "Cannot set element to non-finite value: INF."
  - `Vector::dot()`, `sum()`, `prod()` and `$magnitude`, and `Matrix::det()`, `trace()`, `norm()`, `p1Norm()` and
    `pInfNorm()`, returned `INF` or `NAN`.

  A non-finite argument still throws `DomainException`. See the README's "Infinity, NAN and Overflow" section.
- **`new Complex()`** throws `OverflowException` when both parts are finite but the magnitude isn't, e.g.
  `new Complex(1.7e308, 1.7e308)`. Previously its `$magnitude` was `INF`.
- **`Vector::mul()` and `Matrix::mul()`** throw `DomainException` for a non-finite scalar, like `div()`. Previously,
  multiplying an empty or zero vector by `INF` succeeded.
- **`Complex::fromString()` and `Rational::fromString()`** include the string in an invalid-format message, quoted and
  abbreviated to 32 characters, e.g. "Cannot convert string '3+4x' to Complex. Invalid format." A string that isn't
  valid UTF-8 is left out.
- **Overflow messages no longer include the operands.** `Complex::div()` gives "Cannot divide Complex numbers. The
  result would overflow.", and `Vector::cross()` gives "Cannot compute cross product. The result would overflow."
- **`Matrix::inv()`, `det()` and `pow()` use LU decomposition for matrices larger than 3×3 (breaking),** instead of
  cofactor expansion, which was O(n!): a 10×10 inverse took about 30 seconds, and now takes well under a millisecond.
  Matrices up to 3×3 still use the closed-form formulas, which give exact results where the arithmetic allows.
- **`Matrix::inv()` detects near-singular matrices (breaking).** It throws `ArithmeticException` "Cannot invert singular
  Matrix." if a pivot is negligible relative to its row, instead of only for an exactly zero determinant. Previously, a
  matrix such as `[[0.1, 0.3], [0.7, 2.1]]`, singular but with a determinant of about 3e-17 due to rounding, gave an
  inverse with elements around 1e16. The message was "Cannot invert Matrix with zero determinant."

### Fixed

- **`Matrix::pow()` gave the wrong result for an exponent above 2^53**, including `PHP_INT_MAX` and `PHP_INT_MIN`,
  because it halved the exponent with float division.
- **`Complex::div()`/`inv()` gave wrong results or threw for a very small or large divisor**, even when the result was
  representable. The textbook formula squares the divisor, which underflows or overflows outside about 1e-154 to 1e154.
  Now uses Smith's algorithm. For example, `1 / (1e-300 + 1e-300i)` threw `DivisionByZeroError`, and `1 / 1e200` gave
  `0`.
- **`Complex::div()`/`inv()`** throw `OverflowException` when the result is too large to represent, e.g. `1 / 1e-320`.
- **`Vector::div()`/`Matrix::div()` by ±INF silently returned a zero vector or matrix.** A non-finite scalar now throws
  `DomainException`.
- **`Vector::magnitude` and `Matrix::norm()` overflowed or underflowed when squaring the elements did**, e.g. the
  magnitude of `[1e-200, 1e-200]` was `0`. The elements are now scaled by a power of 2 first, which is exact, so
  ordinary results are unchanged.
- **`Vector::normalize()`** gave `[0, 0]` for a huge vector and threw for a tiny one. Now scales first, like
  `magnitude`.
- **`Vector::cross()`** threw a misleading `fromArray()` message on overflow, and threw for huge parallel vectors, whose
  cross product is zero. It now gives the right result whenever it fits, and throws `OverflowException` otherwise.
- **`Matrix::inv()` threw "zero determinant" for a matrix of uniformly tiny elements**, e.g.
  `[[1e-300, 0], [0, 1e-300]]`, because the determinant underflowed to 0, and overflowed for huge ones. In that case,
  the matrix is now scaled by a power of 2, which is exact, and inverted again. Other matrices aren't scaled, so their
  results are unchanged.
- **`Complex::sqrt()` and `roots()` weren't exact on the axes**, because they used the polar form, e.g. `sqrt(-1)` was
  `6.1e-17 + i`. `sqrt()` now uses a numerically stable formula, so `sqrt(-4)` is exactly `2i` and `sqrt(3 + 4i)` is
  exactly `2 + i`. It also no longer loses precision for a subnormal number. Roots that lie on an axis are exact, e.g.
  the 4th roots of 1 are exactly 1, i, -1 and -i, and `roots(2)` gives `sqrt()` and its negative. `pow(0.5)` uses
  `sqrt()`.
- **`Complex::roots()` gave an inexact magnitude for degree 3 or more**, because 1/n is rounded, e.g. `64 ** (1/3)` is
  `3.9999999999999996`. One step of Newton's method now refines it, so the real cube root of -64 is exactly -4. It's
  never less accurate, and is within one unit in the last place.

### Documentation

- Example variables follow consistent naming: `$z`, `$r`, `$v` or `$m` for a single `Complex`, `Rational`, `Vector` or
  `Matrix`, numbered from 1 (`$z1`, `$z2`, ...) for several, and `$idMat` for an identity matrix.

### Tests

- Test variables follow the same naming as the documentation examples.

---

## [4.0.1] - 2026-10-10

Reverses an unintended breaking change in 4.0.0, released the same day.

### Changed

- **`Vector::normalize()`** is back to its 2.x/3.x behaviour: it returns a new unit vector and leaves the original
  unchanged. In 4.0.0 it normalized in place and returned `void`, so existing code such as `$unit = $v->normalize();`
  still ran but silently set `$unit` to `null`.

### Removed

- **`Vector::normalize()`'s in-place form and `Vector::normalized()`**, both added in 4.0.0. Use `normalize()`.

---

## [4.0.0] - 2026-10-10

Requires `oceanmoon/core` `^4.0`. Breaking changes are marked **(breaking)**. The headline changes:
`ArithmeticException` replaces `DivisionByZeroError`, the general-purpose conversion methods are gone, comparison
methods only accept their own type, and serialization support is removed.

### Added

- **`Vector`/`Matrix` element-wise operations:** `hadamardMul()`, `hadamardDiv()` and `reciprocal()`. `hadamardDiv()`
  and `reciprocal()` throw `ArithmeticException` for a zero element.
- **`Vector::sum()`**, **`Vector::prod()`** - sum and product of all elements.
- **`Vector::outer()`** - outer product, returning an m×n `Matrix`. The vectors don't need to be the same size.
- **`Vector::mul()`** accepts a `Matrix`, treating the vector as a row vector (_xA_).
- **`Vector::normalize()`** - normalizes the vector in place. See Changed for the method previously called
  `normalize()`.
- **`Matrix::mulVector()`** - multiplies the matrix by a vector (_Ax_), returning a `Vector`. Replaces
  `Matrix::mul(Vector)` (see Changed).
- **`Matrix::copy()`**, **`Matrix::paste()`**, **`Matrix::resize()`** - extract a sub-matrix, paste one matrix into
  another in place, and return a resized copy (zero-filled when growing).
- **`Matrix` implements `ArrayAccess`** - `$m[$row]` returns the live row `Vector`, so `$m[$row][$col] = $x` works.
- **`Vector` and `Matrix` implement `Countable`** - `count()` returns the element count (`rowCount * columnCount` for a
  `Matrix`).
- **`Complex::round()`** - rounds both parts, with optional `RoundingMode` (default `HalfAwayFromZero`).
- **`Rational::isInt()`** - whether the denominator is 1.
- **`Rational::sign()`** - `-1`, `0` or `1`, with the same `$zeroForZero` parameter as `Numbers::sign()`.

### Changed

- **`ArithmeticException` replaces `DivisionByZeroError` (breaking).** Thrown for operations with no defined result:
  - `Complex`: division by zero, zero to a negative or complex power, logarithm of zero or with base 0 or 1.
  - `Rational`: zero denominator, reciprocal of zero, division by zero, zero to a negative power.
  - `Vector`: division by zero, normalizing a zero vector.
  - `Matrix`: inverting, dividing by, or raising to a negative power a non-invertible matrix.
- **Comparison methods only accept their own type (breaking)**, throwing `InvalidArgumentException` for anything else:
  - `Vector::equal()`/`approxEqual()` and `Matrix::equal()`/`approxEqual()` no longer accept arrays, or a single-column
    `Matrix` for `Vector`.
  - `Complex::equal()`/`approxEqual()` accept `Complex`, `int` or `float`.
  - `Rational::compare()` (and the methods built on it) throws `InvalidArgumentException` instead of
    `IncomparableTypesException`.
- **Non-finite values are rejected consistently (breaking):**
  - `Vector::set()`/`Matrix::set()` and `fromArray()` throw `DomainException` for `±INF` or `NAN`. Because every
    arithmetic method uses `set()`, a non-finite result (e.g. from overflow) now throws instead of propagating.
  - `Complex::equal()`/`approxEqual()` and `Rational::approxEqual()` throw `DomainException` for a `NAN` argument and
    return `false` for `±INF`.
- **`Vector::fromArray()`/`Matrix::fromArray()` (breaking):** non-sequential keys now throw `DomainException` instead of
  being re-indexed. Call `array_values()` first. Other shape errors are also `DomainException`. Ragged matrix rows throw
  `LengthException`.
- **`Rational::fromFloat()`** throws `DomainException` for a value too large or too small to represent, instead of
  `OverflowException`/`UnderflowException` **(breaking)**.
- **`Rational::add()`/`sub()`/`mul()`/`div()` no longer accept `float` (breaking)** - convert with `fromFloat()` first.
- **`Matrix::mul()` no longer accepts a `Vector` (breaking)** - use `mulVector()`. Its return type is now always
  `Matrix`.
- **`Matrix::div()` no longer accepts a `Matrix` (breaking)** - `A / B` is ambiguous about multiplication order. Use
  `$a->mul($b->inv())` or `$b->inv()->mul($a)`.
- **`Matrix::setRow()`/`setColumn()` only accept a `Vector` (breaking)**, not an array. Wrap arrays with
  `Vector::fromArray()`.
- **Renames (breaking):**

  | Old                           | New                                |
  | ----------------------------- | ---------------------------------- |
  | `Vector::$size`               | `Vector::$count`                   |
  | `Vector::normalize()`         | `Vector::normalized()`             |
  | `Matrix::transpose()`         | `Matrix::t()`                      |
  | `Complex::parse()`            | `Complex::fromString()`            |
  | `Rational::parse()`           | `Rational::fromString()`           |
  | `OceanMoon\Math\I`            | `OceanMoon\Math\M_I`               |

- **Renamed parameters (breaking for named arguments):** `new Vector(size:)` → `count:`, `Complex::pow(other:)` →
  `exp:`, `Complex::roots(n:)` → `degree:`, `Rational::pow(exponent:)` → `exp:`, `Matrix::pow(power:)` → `exp:`.
- **`Rational::round()`** takes an optional `RoundingMode` (default `HalfAwayFromZero`, the previous behavior). Rounding
  uses exact integer arithmetic, so there's no precision loss near ties or for large values.
- **`Complex::fromString()`** accepts only lowercase `i` as the imaginary unit: `j`, `J` and `I` now throw
  `FormatException` **(breaking)**. This leaves `j` and `k` free for a planned `Quaternion` class.
- **`Vector::__toString()`** uses ordered-tuple notation (`⟨1, 2, 3⟩`) instead of box-drawing characters.
- **`Vector::$magnitude`** is recomputed on every read instead of cached. **`Complex::$magnitude`/`$phase`** are
  computed in the constructor instead of lazily.
- **`Rational::pow(1)`** returns a new, equal instance instead of `$this`.
- **`Complex::pow()`** with an integer exponent uses exponentiation by squaring instead of `exp()`/`ln()`, giving exact
  results for small integer powers.
- **`Floats::TAU`** (Core 2) was removed in Core 3. Use `use const OceanMoon\Core\M_TAU;` instead.

### Fixed

- **`Matrix::det()`** of the 0×0 matrix returns `1.0` (the empty product) instead of `0.0`.

### Removed

- **Conversion methods (breaking):**

  | Removed                                                  | Use instead                                        |
  | -------------------------------------------------------- | -------------------------------------------------- |
  | `Complex::fromArray()`, `toArray()`                      | Constructor, or `$z->real`/`$z->imaginary`         |
  | `Complex::fromObject()`, `toObject()`                    | Constructor, or `$z->real`/`$z->imaginary`         |
  | `Complex::toComplex()`, `Rational::toRational()`         | Constructor, `fromFloat()` or `fromString()`       |
  | `Complex::fromVector()`, `toVector()`                    | `new Complex($v[0], $v[1])`, `Vector::fromArray()` |
  | `Vector::toMatrix(bool $asRow)`                          | `toRowMatrix()`, `toColumnMatrix()`                |
  | `Vector::format(bool $asRow)`                            | `__toString()`                                     |

- **`Complex::i()` (breaking)** - use the `M_I` constant.
- **`Complex::EPSILON` (breaking).**
- **Twelve reciprocal trig methods on `Complex` (breaking):** `sec()`, `csc()`, `cot()`, `asec()`, `acsc()`, `acot()`
  and their hyperbolic forms. Use the underlying method with `inv()`, e.g. `$z->cos()->inv()` for `sec()`.
- **Serialization (breaking):** `Complex` and `Rational` no longer implement `JsonSerializable`, and
  `__serialize()`/`__unserialize()` are gone.
- **`ConversionException`** is no longer thrown anywhere in this package.

### Documentation

- `Complex.md`, `Rational.md`, `Vector.md` and `Matrix.md` rewritten for the current API, with sections in source order.
- New: `docs/Globals.md` (`M_I`), a "Strict Typing" section in `README.md`.

### Tests

- Shared float tolerance is a single `EPSILON` constant in `tests/bootstrap.php`. Coverage remains 100%.

---

## [3.0.0] - 2026-07-09

### Breaking

- **`Rational::__construct()` no longer accepts `float`** - it's now `__construct(int $num = 0, int $den = 1)`. Code
  calling `new Rational(0.5)` (or any float argument) will now throw a `TypeError`. Use the new `Rational::fromFloat()`
  instead, which handles the approximation via continued fractions explicitly. This mirrors `Complex`'s existing
  pattern: a tight, exact constructor plus dedicated factory methods for conversions that require real logic, rather
  than an implicit fallback baked into `new`.

### Added

- **`Complex`**: `toObject()`, `toVector()`, `fromArray()`, `fromObject()`, `fromVector()` - conversion to/from plain
  objects, arrays, and `Vector`. `toComplex()` is now `public` (was previously private), so it can be called directly.
  `Complex` now implements `JsonSerializable`, and gained `__serialize()`/`__unserialize()` for correct native PHP
  serialization (validated and canonicalized via the constructor, rather than PHP's default property-hydration bypassing
  it).
- **`Rational`**: `fromFloat()` - the float-to-Rational conversion extracted from the constructor (see Breaking, above).
  `Rational` now implements `JsonSerializable`, and gained `__unserialize()` for the same constructor-validated
  serialization guarantee as `Complex`. (No corresponding `__serialize()`: unlike `Complex`, `Rational` has no computed
  properties to exclude from the payload, so PHP's default serialization already matches what a custom one would
  produce.)
- **`OceanMoon\Math\I`** - the imaginary unit as a real language-level constant (`src/constants.php`, wired up via
  Composer's `files` autoload), a convenient abbreviation for `Complex::i()`.
- **`Matrix`**: `calcDet()` gained a closed-form 3×3 fast path (Sarrus' Rule), which also speeds up `inv()` on 4×4
  matrices via its minors.

### Changed

- Simplified scalar value-parameter types from `int|float` to `float` across `Complex`, `Vector`, and `Matrix`. Integer
  arguments are still accepted, because PHP widens `int` to `float` even under `strict_types`, so this change is
  backward compatible.
  - `Complex`: `__construct()`, `fromPolar()`, `add()`, `sub()`, `mul()`, `div()`, `pow()`, `log()`.
  - `Vector`: `set()`, `mul()`, `div()`.
  - `Matrix`: `set()`, `mul()`, `div()`.
- `Rational::toRational()`'s parameter widened from `int|float|string|self` to `mixed`; it now throws
  `InvalidArgumentException` for a value of any other type, rather than a language-level `TypeError`. Backward
  compatible: every previously-accepted type still works.
- Fixed a latent bug in `Complex::toComplex()`: the `Vector` branch was unreachable, because the generic `is_object()`
  check ran first and always failed it (`Vector` has no public `real`/`imaginary` properties). Reordered so `Vector` is
  checked first.

### Removed

- `Rational::__serialize()` - redundant now that `Rational` has no computed properties to exclude from serialization;
  `__unserialize()` alone provides the correctness guarantee (routing through the constructor rather than bypassing it).

---

## [2.0.0] - 2026-06-18

### Changed

- **Renamed package** from `galaxon/math` to `oceanmoon/math`. Update your `composer.json` require accordingly.
- **Renamed PHP namespaces** from `Galaxon\Math\*` to `OceanMoon\Math\*` throughout all source and test files.
- Updated runtime dependency `galaxon/core` → `oceanmoon/core: ^2.0`.
- Updated dev dependency `galaxon/coding-standard` → `oceanmoon/coding-standard: ^2.0`.
- `composer.json`: updated author email, homepage, and support URLs to Ocean Moon Software.

---

## [1.2.1] - 2026-04-09

### Changed

- **`Complex::__toString()`** - Now routes both real and imaginary parts through `Floats::format()` instead of casting
  directly to string. IEEE-754 representation noise from arithmetic (e.g. `0.1 + 0.2 == 0.30000000000000004`) is
  suppressed: `(string)(new Complex(0.1 + 0.2, 0))` now renders as `'0.3'` instead of `'0.30000000000000004'`. Pinned
  outputs for clean values (`'5'`, `'-3.14'`, `'3 + 4i'`, etc.) are unchanged.
- **`Matrix::__toString()`** - Cells are now formatted via `Floats::format()` up front, so column-width calculations and
  rendering both use the cleaned strings. Without this, a single noisy cell like `0.1 + 0.2` would dominate the column
  width with its 17-digit representation. `Vector::__toString()` inherits the fix automatically via its delegation to
  `Matrix`.
- **`composer.json`** - Bumped `galaxon/core` constraint to `^1.6`. Required for `Floats::format()` and for the trait
  namespace reorganisation shipped in Core v1.6.0.
- **Trait namespace updates** - Updated `use` statements throughout the package to match Core v1.6.0's trait
  reorganisation:
  - `Galaxon\Core\Traits\ApproxComparable` → `Galaxon\Core\Traits\Comparison\ApproxComparable` (in `Vector`)
  - `Galaxon\Core\Traits\ApproxEquatable` → `Galaxon\Core\Traits\Comparison\ApproxEquatable` (in `Rational`)
  - `Galaxon\Core\Traits\FloatAssertions` → `Galaxon\Core\Traits\Asserts\FloatAssertions` (in `MatrixLinearAlgebraTest`,
    `VectorArithmeticTest`)
- **Documentation** - Updated "See Also" links in `Complex.md`, `Matrix.md`, `Rational.md`, and `Vector.md` to point at
  the new `Traits/Comparison/` paths for `ApproxEquatable`/`ApproxComparable`.

### Tests

- Added `testToStringSuppressesFloatingPointNoise` to `ComplexConversionTest` and `MatrixConversionTest`, pinning the
  regression so future refactors that revert to raw float-to-string casts will fail loudly.

---

## [1.2.0] - 2026-03-31

### Added

- **Matrix**: `setRow()`, `setColumn()` - set a row or column from a Vector or array.
- **Matrix**: `trace()` - sum of diagonal elements (square matrices only).
- **Matrix**: `norm()`, `p1Norm()`, `pInfNorm()` - Frobenius, P1, and P-infinity matrix norms.
- **Vector**: `normalize()` - return a unit vector with the same direction.
- **Rational**: `toMixedNumber()` - convert to integer part and fractional remainder with trunc/frac semantics.
- **Complex, Rational, Matrix**: `sqr()` method for squaring values. Uses `mul($this)` for efficiency, equivalent to
  `pow(2)`.
- **Complex**: Constructor now validates that both parts are finite (rejects ±INF and NAN).

### Changed

- **Rational**: Renamed properties `$num` → `$numerator` and `$den` → `$denominator`.
- **Rational**: Zero denominator now throws `DivisionByZeroError` (was `DomainException`).
- **Rational**: `floatToRatio()` is now private (use the constructor instead).
- **Matrix**: Removed `ArrayAccess` interface, because bracket syntax was misleading for element-level mutation
  (`$m[1][1] = 9` silently failed).
- **Matrix**: `rowCount` is now a stored `private(set)` property (was derived from data).
- **Vector**: `magnitude` is now cached with a property hook, invalidated on mutation (was recomputed every access).
- **Vector**: `size` is now a stored `private(set)` property (was recomputed via `count()`).
- **Exception messages** standardised across all four classes to follow "Cannot X" convention with offending values and
  concise constraints.
- Consistent region structure across all four source files.

### Fixed

- `Rational::floatToRatio()` - sign is now correctly placed on the numerator (not denominator) for the `1/PHP_INT_MAX`
  boundary case.
- `Rational::floatToRatio()` - `(float)PHP_INT_MAX` and `(float)PHP_INT_MIN` now return correct boundary values instead
  of throwing.
- `Complex.php` - missing `#endregion` for ArrayAccess implementation region.
- `Matrix` - `rowCount` property was not initialised in constructor after property declaration change.

### Documentation

- Added complexity warnings to `Matrix::inv()` (O(n! × n²)) and `calcDet()` (O(n!)).
- Added `See Also` sections to all four class docs.
- Expanded Rational constructor docs with float conversion, irrational approximation, and error examples.
- Added documentation for all new methods.
- Removed `ArrayAccess` section from Matrix.md.
- Contributing section removed from README (link moved to Support).

### Tests

- New tests for `setRow`, `setColumn`, `trace`, `norm`, `p1Norm`, `pInfNorm`, `normalize`, `toMixedNumber`.
- Complex constructor validation tests (INF, -INF, NAN for both parts).
- Rational constructor tests for float-to-ratio paths (migrated from removed `floatToRatio()` tests).
- Created `MatrixLinearAlgebraTest` (moved from `MatrixArithmeticTest`).
- Removed `ArrayAccess` tests from `MatrixConversionTest`.
- Added regions to `ComplexTrigonometricTest`, `MatrixElementsTest`, `RationalConstructorTest`.

---

## [1.1.0] - 2026-03-21

### Added

- **Vector** - Mutable class for mathematical vectors with float-only storage
  - Constructor and factory method: `fromArray()`
  - Element access: `get()`, `set()`, `ArrayAccess` interface
  - Arithmetic: `add()`, `sub()`, `mul()`, `div()`, `dot()`, `cross()`
  - Properties: `magnitude()`, `normalize()`, `size`
  - Comparison: `equal()`, `approxEqual()` via `ApproxEquatable` trait
  - Conversion: `toArray()`, `toMatrix()`, `format()`, `__toString()`
  - Comprehensive test suite (5 test files, 59 tests)
  - Full documentation (Vector.md)

- **Matrix** - Mutable class for mathematical matrices with float-only storage
  - Constructor with rows/columns dimensions
  - Element access: `get()`, `set()`, `ArrayAccess` for row-level access
  - Arithmetic: `add()`, `sub()`, `mul()`, `div()`, `pow()`
  - Operations: `det()`, `transpose()`, `inverse()`, `identity()`
  - Vector multiplication support
  - Comparison: `equal()`, `approxEqual()` via `ApproxEquatable` trait
  - Conversion: `toArray()`, `format()`, `__toString()` with box-drawing characters
  - Comprehensive test suite (5 test files)
  - Full documentation (Matrix.md)

### Changed

- **Complex** and **Rational** - Use `FormatException` for parse errors on empty strings
- README updated with Vector and Matrix sections
- Documentation cross-references to Core `ApproxEquatable` trait

### Fixed

- **Rational::floatToRatio()** - Fixed PHP warnings from `(int)` cast overflow near `PHP_INT_MAX`/`PHP_INT_MIN`;
  tightened boundary checks
- **Matrix::pow()** - Fixed static method call (`self::identity()` instead of `$this->identity()`)
- **Complex::parse()** - Corrected exception type for empty string input
- **Vector::div()** - Added division by zero protection
- **Matrix** constructor - Corrected exception type for negative dimensions

---

## [1.0.0] - 2026-01-05

### First Stable Release

This is the first stable release of Galaxon Math, ready for publication on Packagist.

### Breaking Changes

- **Exception types standardized** - All exceptions now use SPL exception types consistently:
  - `Rational::compare()` - Throws `IncomparableTypesException` for type mismatches (was `TypeError`)
  - `Rational` constructor/parse - Throws `UnderflowException`/`OverflowException` for range errors (was
    `RangeException`)
  - `Complex` and `Rational` - Use `DomainException` for invalid values consistently

### Added

- **Rational::simplify()** - Now tolerates `PHP_INT_MIN` when the other value is a multiple of 2
  - Allows fractions like `PHP_INT_MIN/2` or `4/PHP_INT_MIN` to be created and simplified
  - Still throws for cases that cannot be simplified (e.g., `PHP_INT_MIN/1`)

### Changed

- **composer.json** - Updated for Packagist publication:
  - Added keywords for discoverability
  - Added author information
  - Added homepage and support URLs
  - Updated dependencies to use Packagist versions (galaxon/core ^1.0)
  - Improved description

### Fixed

- Fixed GitHub URLs in README.md (`PHP-Math` → `Galaxon-PHP-Math`)
- Removed FloatWithError reference from README.md (class is in Quantities package)

---

## [0.2.0] - 2025-12-09

### Changed (Breaking Changes)

- **Complex**: Renamed `equals()` → `equal()` for exact equality (no tolerance)
- **Complex**: Added `approxEqual()` method with configurable relative and absolute tolerances
- **Complex**: Now uses `ApproxEquatable` trait instead of implementing `Equatable` interface
- **Rational**: Renamed `equals()` → `equal()` for exact equality
- **Rational**: Added `approxEqual()` and `approxCompare()` methods with configurable tolerances
- **Rational**: Renamed comparison methods: `isLessThan()` → `lessThan()`, `isGreaterThan()` → `greaterThan()`,
  `isLessThanOrEqual()` → `lessThanOrEqual()`, `isGreaterThanOrEqual()` → `greaterThanOrEqual()`
- **Rational**: Now uses `ApproxComparable` trait instead of `Comparable` trait
- **Rational**: `compare()` method now performs exact comparison (no epsilon parameter)

### Improved

- Updated comprehensive documentation for comparison methods with detailed examples
- Added new comparison tests for both Complex and Rational classes
- Updated dependencies: PHPStan 2.1.33, PHPUnit 12.5.2, nikic/php-parser 5.7.0, theseer/tokenizer 2.0.1
- Added slevomat/coding-standard 8.25.1 to CodingStandard package
- Enhanced composer scripts with verbose output flags (`phpcbf -vp`)

---

## [0.1.0] - 2025-01-18

### Added

- **Complex** - Immutable class for complex numbers (a + bi)
  - Constructor and factory methods: `fromPolar()`, `parse()`, `i()`
  - Basic arithmetic: `add()`, `sub()`, `mul()`, `div()`, `neg()`, `conj()`, `inv()`
  - Transcendental functions: `exp()`, `ln()`, `log()`, `pow()`, `sqrt()`, `cbrt()`, `roots()`
  - Trigonometric functions: `sin()`, `cos()`, `tan()`, `sec()`, `csc()`, `cot()`
  - Inverse trigonometric: `asin()`, `acos()`, `atan()`, `asec()`, `acsc()`, `acot()`
  - Hyperbolic functions: `sinh()`, `cosh()`, `tanh()`, `sech()`, `csch()`, `coth()`
  - Inverse hyperbolic: `asinh()`, `acosh()`, `atanh()`, `asech()`, `acsch()`, `acoth()`
  - Properties: `real`, `imaginary`, `magnitude`, `phase` (cached)
  - Polar/rectangular form conversion
  - String parsing with flexible format support
  - Epsilon-based equality comparison
  - ArrayAccess interface for `[0]`/`[1]` access
  - Implements `Equatable` interface

- **Rational** - Immutable class for exact rational number arithmetic
  - Automatic reduction to simplest form (e.g., 6/8 → 3/4)
  - Canonical form (positive denominator, sign in numerator)
  - Basic arithmetic: `add()`, `sub()`, `mul()`, `div()`, `neg()`, `inv()`, `pow()`, `abs()`
  - Rounding methods: `floor()`, `ceil()`, `round()`
  - Comparison: `compare()`, `equals()`, `isLessThan()`, `isGreaterThan()`, etc.
  - Conversion: `toFloat()`, `toInt()`, `__toString()`
  - Factory methods: `parse()`, `toRational()`
  - Float-to-ratio conversion using continued fractions algorithm
  - Cross-cancellation in multiplication to prevent overflow
  - Overflow detection for safe integer arithmetic
  - Implements `Equatable` interface, uses `Comparable` trait

### Requirements

- PHP ^8.4
- galaxon/core package

### Development

- PSR-12 coding standards
- PHPStan level 9 static analysis
- PHPUnit test coverage
- Comprehensive test suite with 100% code coverage
