# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

> **Version not yet decided.** This batch includes several breaking changes (exception types changed
> throughout, several conversion methods removed, comparison methods narrowed to stricter type
> acceptance) that likely warrant a major version bump — to be decided before this is tagged as a
> release.

### Added

- **`ArithmeticException`** (`OceanMoon\Core\Exceptions\ArithmeticException`) is now thrown for
  arithmetic operations with no defined result — `Complex::div()` (division by zero), `pow()`
  (raising zero to a negative or complex power), `ln()`/`log()` (logarithm of zero, or with base 0 or
  1); `Rational`'s constructor/`inv()`/`div()`/`pow()` (zero denominator, reciprocal of zero, division
  by zero, raising zero to a negative power); `Vector::div()`/`normalize()` (zero scalar/magnitude);
  and `Matrix::inv()`/`div()`/`pow()` (zero determinant / non-invertible matrix). This displaces
  `DivisionByZeroError` throughout the package — see Removed.
- **`Vector::hadamard()`**, **`Matrix::hadamard()`** — element-wise (Hadamard) product of two
  same-shaped vectors/matrices.
- **`Vector::sum()`**, **`Vector::prod()`** — sum and product of all elements.
- **`Matrix::copy()`** — extract a rectangular sub-matrix.
- **`Matrix::paste()`** — copy another matrix's elements into this one in place, at a given offset.
- **`Matrix::resize()`** — return a new matrix with different dimensions, built from `copy()`/
  `paste()`, zero-filling on growth and truncating on shrink.
- **`Vector`** and **`Matrix`** now implement `Countable`: `count()` returns `size` for `Vector`, and
  `rowCount * columnCount` for `Matrix`.

### Changed

- **Comparison methods narrowed to a small, fixed set of accepted types**, matching Core's finalized
  comparison-trait policy (strict `instanceof self` checks, throw `InvalidArgumentException` for
  anything else — no silent conversion): `Vector::equal()`/`approxEqual()` and
  `Matrix::equal()`/`approxEqual()` now only accept an instance of the same class (previously accepted
  arrays and, for `Vector`, single-column `Matrix` conversions too). `Complex::equal()`/`approxEqual()`
  accept `Complex`, `int`, or `float` only — the one deliberate, documented exception to same-type-only
  comparison in this package, since a bare number is genuinely part of the same numeric domain as a
  `Complex`.
- **`OceanMoon\Math\I`** is now **`OceanMoon\Math\M_I`**, matching Core's `M_TAU` naming convention.
  Code using `Floats::TAU` should use the new `OceanMoon\Core\Globals\M_TAU` constant directly instead
  (moved out of `Floats`).
- **`Vector::fromArray()`** and **`Matrix::fromArray()`** now reject arrays with non-sequential keys
  (`DomainException`) instead of silently re-indexing them. If you were relying on the re-indexing
  behavior, call `array_values()` explicitly before passing the array in. Both methods' shape-validation
  failures (wrong list-ness, wrong element count per row, non-numeric elements) are now uniformly
  `DomainException` — the array argument is already correctly typed by the time these methods run, so a
  malformed array is a domain/value problem, not a type-conversion one.
- **`Rational::compare()`** (and therefore `equal()`, `lessThan()`, etc.) now accepts Rational-format
  strings (e.g. `'1/2'`) via the (now-private) `toRational()` helper, not just plain numeric strings —
  a capability expansion.
- Consolidated the float-comparison tolerance used throughout the test suite into a single global
  `EPSILON` constant (`tests/bootstrap.php`), replacing the `Complex::EPSILON` class constant and
  various ad-hoc tolerance values scattered across individual test files.
- Replaced `assertApproxEqual()` (from Core's `FloatAssertions` trait) with PHPUnit's own
  `assertEqualsWithDelta(..., EPSILON)` in the `Matrix` and `Vector` test suites, matching the
  pattern already used everywhere else in this package's tests. `FloatAssertions`'s relative-tolerance
  behavior wasn't being exercised by any of the seven call sites it had here (all in a similar,
  modest magnitude range), so the switch is a simplification, not a loss of coverage. The trait
  itself is untouched in Core.

### Fixed

- **`Vector::toColumnMatrix()`** — an empty vector previously converted to a degenerate 0×0 matrix
  instead of the correct 0×1, because it routed through `Matrix::fromArray()`, whose empty-array
  shortcut can't distinguish the two shapes. Now constructs the matrix directly, always producing a
  proper n×1 shape. This also fixes a downstream bug in **`Matrix::mul(Vector)`**: multiplying a
  0-column matrix by an empty vector previously threw a spurious `OutOfRangeException` instead of
  returning the correct zero-length-result `Vector`.
- **`Vector::offsetSet()`** — the `ArrayAccess` implementation had an unconditional
  `throw new OutOfRangeException(...)` placed before the actual offset-validity check, making every
  assignment via `$vector[$i] = $value` fail regardless of whether `$i` was valid. Guard reordered to
  match `offsetGet()`'s pattern.
- **`Rational::fromString()`** — a variable had literally lost its name during a prior edit
  (`$ = Floats::tryConvertToInt($n);`, `is_int($)`, `$n = $;`), a hard syntax error. Restored as `$i`.
- **`Rational::fromString()`** — the zero-denominator case (`'5/0'`) was being caught by an inner
  `catch (DomainException)` block written for a different, narrower case (the `PHP_INT_MIN`
  unsimplifiable-ratio edge case), causing it to fall through to `fromFloat($n / $d)` — which itself
  divides by zero — instead of reporting the original, more specific error. Introduced when the zero-
  denominator case was migrated from `DivisionByZeroError` (an `Error`, never caught by that block) to
  `ArithmeticException` (a `DomainException` subtype, which *is* caught by it). Fixed by checking
  `$d === 0` explicitly before the `try`, bypassing the unrelated fallback entirely.

### Removed

- **`ConversionException`** (from Core) is no longer used anywhere in this package. `Complex`,
  `Rational`, `Vector`, and `Matrix` no longer have general-purpose `mixed`-accepting conversion
  factories (`fromObject()`, `toComplex()`, `toVector()`, `toMatrix()` are all gone — see below); what
  remains are narrow, explicitly-typed constructors/factories and the strict-type comparison methods
  above, so there's no longer a "value of unknown type being converted" scenario for this exception to
  describe.
- **`Complex::fromArray()`**, **`Complex::fromObject()`**, **`Complex::toComplex()`** — removed
  entirely, not just changed. Construct via the constructor, `fromString()`, or `fromPolar()` instead.
- **`Vector::toVector()`**, **`Matrix::toMatrix()`** — the general-purpose `mixed`-accepting conversion
  factories floated earlier in this Unreleased cycle were removed again along with the comparison-method
  narrowing above; they existed to serve comparison methods that no longer accept arbitrary convertible
  input.
- **`Vector::toMatrix(bool $asRow)`**, **`Vector::format(bool $asRow)`** — replaced by
  `toRowMatrix()`/`toColumnMatrix()`. `__toString()` no longer delegates to `format()`; it now
  renders directly using ordered tuple notation (`⟨1, 2, 3⟩`) instead of box-drawing characters.
- `Complex::EPSILON` — superseded by the global `EPSILON` constant, above.
- **`Complex::i()`** — redundant now that the `OceanMoon\Math\M_I` constant does the same thing; use
  the constant directly.
- **`Complex::identical()`** — added earlier in this Unreleased cycle, removed again along with Core's
  `Equatable::identical()`, which it depended on.
- **`Complex::fromVector()`**, **`Complex::toVector()`**, and the `Vector` branch of
  **`Complex::toComplex()`** (moot now that `toComplex()` itself is gone) — `Complex` no longer has any
  dependency on `Vector`, so the extension currently in development (which implements `Complex` but not
  the rest of the package) can support it standalone. Convert via `toArray()`/`fromArray()` and
  `Vector::toArray()`/`Vector::fromArray()` instead.
- **`DivisionByZeroError`** — no longer thrown anywhere in this package; displaced by `ArithmeticException`
  (see Added).

### Documentation

- Rewrote `Complex.md`, `Rational.md`, `Vector.md`, and `Matrix.md` to match the current API:
  updated all exception types (`ArithmeticException` in place of `DivisionByZeroError`/
  `ConversionException` where applicable), removed documentation for every removed conversion
  method above, added documentation for every new method above, and reorganised section order to
  match each class's source region order (most notably moving "Conversion Methods" in
  `Rational.md`/`Matrix.md`, and splitting the old "Element Access"/"Get/Set Matrix Elements"
  headings into separate Inspection/Modification sections).
- Fixed a pre-existing doc bug in all four files: `equal()`/`approxEqual()` docs claimed to "return
  false" for unconvertible values; corrected to document the (correct, existing) throwing behavior.

---

## [3.0.0] - 2026-07-09

### Breaking

- **`Rational::__construct()` no longer accepts `float`** — it's now `__construct(int $num = 0, int $den = 1)`. Code calling `new Rational(0.5)` (or any float argument) will now throw a `TypeError`. Use the new `Rational::fromFloat()` instead, which handles the approximation via continued fractions explicitly. This mirrors `Complex`'s existing pattern: a tight, exact constructor plus dedicated factory methods for conversions that require real logic, rather than an implicit fallback baked into `new`.

### Added

- **`Complex`**: `toObject()`, `toVector()`, `fromArray()`, `fromObject()`, `fromVector()` — conversion to/from plain objects, arrays, and `Vector`. `toComplex()` is now `public` (was previously private), so it can be called directly. `Complex` now implements `JsonSerializable`, and gained `__serialize()`/`__unserialize()` for correct native PHP serialization (validated and canonicalized via the constructor, rather than PHP's default property-hydration bypassing it).
- **`Rational`**: `fromFloat()` — the float-to-Rational conversion extracted from the constructor (see Breaking, above). `Rational` now implements `JsonSerializable`, and gained `__unserialize()` for the same constructor-validated serialization guarantee as `Complex`. (No corresponding `__serialize()`: unlike `Complex`, `Rational` has no computed properties to exclude from the payload, so PHP's default serialization already matches what a custom one would produce.)
- **`OceanMoon\Math\I`** — the imaginary unit as a real language-level constant (`src/constants.php`, wired up via Composer's `files` autoload), a convenient abbreviation for `Complex::i()`.
- **`Matrix`**: `calcDet()` gained a closed-form 3×3 fast path (Sarrus' Rule), which also speeds up `inv()` on 4×4 matrices via its minors.

### Changed

- Simplified scalar value-parameter types from `int|float` to `float` across `Complex`, `Vector`, and `Matrix`. Integer arguments are still accepted, because PHP widens `int` to `float` even under `strict_types`, so this change is backward compatible.
  - `Complex`: `__construct()`, `fromPolar()`, `add()`, `sub()`, `mul()`, `div()`, `pow()`, `log()`.
  - `Vector`: `set()`, `mul()`, `div()`.
  - `Matrix`: `set()`, `mul()`, `div()`.
- `Rational::toRational()`'s parameter widened from `int|float|string|self` to `mixed`; it now throws `InvalidArgumentException` for a value of any other type, rather than a language-level `TypeError`. Backward compatible — every previously-accepted type still works.
- Fixed a latent bug in `Complex::toComplex()`: the `Vector` branch was unreachable, because the generic `is_object()` check ran first and always failed it (`Vector` has no public `real`/`imaginary` properties). Reordered so `Vector` is checked first.

### Removed

- `Rational::__serialize()` — redundant now that `Rational` has no computed properties to exclude from serialization; `__unserialize()` alone provides the correctness guarantee (routing through the constructor rather than bypassing it).

---

## [2.0.0] - 2026-06-18

### Changed

- **Renamed package** from `galaxon/math` to `oceanmoon/math` — update your `composer.json` require accordingly.
- **Renamed PHP namespaces** from `Galaxon\Math\*` to `OceanMoon\Math\*` throughout all source and test files.
- Updated runtime dependency `galaxon/core` → `oceanmoon/core: ^2.0`.
- Updated dev dependency `galaxon/coding-standard` → `oceanmoon/coding-standard: ^2.0`.
- `composer.json`: updated author email, homepage, and support URLs to Ocean Moon Software.

---

## [1.2.1] - 2026-04-09

### Changed

- **`Complex::__toString()`** — Now routes both real and imaginary parts through `Floats::format()` instead of casting directly to string. IEEE-754 representation noise from arithmetic (e.g. `0.1 + 0.2 == 0.30000000000000004`) is suppressed: `(string)(new Complex(0.1 + 0.2, 0))` now renders as `'0.3'` instead of `'0.30000000000000004'`. Pinned outputs for clean values (`'5'`, `'-3.14'`, `'3 + 4i'`, etc.) are unchanged.
- **`Matrix::__toString()`** — Cells are now formatted via `Floats::format()` up front, so column-width calculations and rendering both use the cleaned strings. Without this, a single noisy cell like `0.1 + 0.2` would dominate the column width with its 17-digit representation. `Vector::__toString()` inherits the fix automatically via its delegation to `Matrix`.
- **`composer.json`** — Bumped `galaxon/core` constraint to `^1.6`. Required for `Floats::format()` and for the trait namespace reorganisation shipped in Core v1.6.0.
- **Trait namespace updates** — Updated `use` statements throughout the package to match Core v1.6.0's trait reorganisation:
  - `Galaxon\Core\Traits\ApproxComparable` → `Galaxon\Core\Traits\Comparison\ApproxComparable` (in `Vector`)
  - `Galaxon\Core\Traits\ApproxEquatable` → `Galaxon\Core\Traits\Comparison\ApproxEquatable` (in `Rational`)
  - `Galaxon\Core\Traits\FloatAssertions` → `Galaxon\Core\Traits\Asserts\FloatAssertions` (in `MatrixLinearAlgebraTest`, `VectorArithmeticTest`)
- **Documentation** — Updated "See Also" links in `Complex.md`, `Matrix.md`, `Rational.md`, and `Vector.md` to point at the new `Traits/Comparison/` paths for `ApproxEquatable`/`ApproxComparable`.

### Tests

- Added `testToStringSuppressesFloatingPointNoise` to `ComplexConversionTest` and `MatrixConversionTest`, pinning the regression so future refactors that revert to raw float-to-string casts will fail loudly.

---

## [1.2.0] - 2026-03-31

### Added

- **Matrix**: `setRow()`, `setColumn()` — set a row or column from a Vector or array.
- **Matrix**: `trace()` — sum of diagonal elements (square matrices only).
- **Matrix**: `norm()`, `p1Norm()`, `pInfNorm()` — Frobenius, P1, and P-infinity matrix norms.
- **Vector**: `normalize()` — return a unit vector with the same direction.
- **Rational**: `toMixedNumber()` — convert to integer part and fractional remainder with trunc/frac semantics.
- **Complex, Rational, Matrix**: `sqr()` method for squaring values. Uses `mul($this)` for efficiency, equivalent to `pow(2)`.
- **Complex**: Constructor now validates that both parts are finite (rejects ±INF and NAN).

### Changed

- **Rational**: Renamed properties `$num` → `$numerator` and `$den` → `$denominator`.
- **Rational**: Zero denominator now throws `DivisionByZeroError` (was `DomainException`).
- **Rational**: `floatToRatio()` is now private (use the constructor instead).
- **Matrix**: Removed `ArrayAccess` interface — bracket syntax was misleading for element-level mutation (`$m[1][1] = 9` silently failed).
- **Matrix**: `rowCount` is now a stored `private(set)` property (was derived from data).
- **Vector**: `magnitude` is now cached with a property hook, invalidated on mutation (was recomputed every access).
- **Vector**: `size` is now a stored `private(set)` property (was recomputed via `count()`).
- **Exception messages** standardised across all four classes to follow "Cannot X" convention with offending values and concise constraints.
- Consistent region structure across all four source files.

### Fixed

- `Rational::floatToRatio()` — sign is now correctly placed on the numerator (not denominator) for the `1/PHP_INT_MAX` boundary case.
- `Rational::floatToRatio()` — `(float)PHP_INT_MAX` and `(float)PHP_INT_MIN` now return correct boundary values instead of throwing.
- `Complex.php` — missing `#endregion` for ArrayAccess implementation region.
- `Matrix` — `rowCount` property was not initialised in constructor after property declaration change.

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

- **Rational::floatToRatio()** - Fixed PHP warnings from `(int)` cast overflow near `PHP_INT_MAX`/`PHP_INT_MIN`; tightened boundary checks
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
  - `Rational` constructor/parse - Throws `UnderflowException`/`OverflowException` for range errors (was `RangeException`)
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
- **Rational**: Renamed comparison methods: `isLessThan()` → `lessThan()`, `isGreaterThan()` → `greaterThan()`, `isLessThanOrEqual()` → `lessThanOrEqual()`, `isGreaterThanOrEqual()` → `greaterThanOrEqual()`
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
