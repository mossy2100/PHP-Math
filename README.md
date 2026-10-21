# Mossy2100 PHP Math package

Provides classes for Complex numbers, Rational numbers, Vectors, and Matrices.

**[License](LICENSE)** | **[Changelog](CHANGELOG.md)** | **[Documentation](docs/)**

![PHP 8.4](docs/logo_php8_4.png)

---

## Description

This package provides classes for working with complex numbers, rational numbers, vectors, and matrices in PHP.

For arithmetic and comparison operators (`$z1 + $z2`, `$r1 < $r2`), install the [Math extension](https://github.com/mossy2100/PHP-Math-extension), which provides the same
classes in C, with operators.

**Key Features:**

- **Complex numbers** - Complex arithmetic, trigonometry, transcendental functions, polar/rectangular conversions, and
  conversion to/from strings.
- **Rational numbers** - Exact fraction arithmetic using integer ratios, automatic simplification, overflow detection,
  and conversion to/from floats and strings.
- **Vectors** - Element-wise arithmetic, dot and cross products, array-style access, and conversion to/from arrays.
- **Matrices** - Matrix arithmetic, inverse, determinant, transpose, power, and matrix-vector multiplication.

The salient features of the package include:

- Careful attention to precision, efficiency, usefulness, clear documentation, and coding standards.
- Seamless interoperation with PHP `int`, `float`, `string`, and `array` types.
- A fluent API that enables expressive operations.
- Expressive exception types and messages.
- Comprehensive tests providing 100% code coverage.

---

## Development and Quality Assurance

[Claude Chat](https://claude.ai) and [Claude Code](https://www.claude.com/product/claude-code) were used in the development of this package. The core classes were designed, coded, and
commented primarily by the author, with Claude providing substantial assistance with code review, suggesting
improvements, debugging, and generating tests and documentation. All code was thoroughly reviewed by the author, and
validated using industry-standard tools including [PHP_Codesniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer/), [PHPStan](https://phpstan.org/) (to level 9), and [PHPUnit](https://phpunit.de/index.html) to ensure full
compliance with [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards and comprehensive unit testing with 100% code coverage. This collaborative
approach has produced a well-designed, production-ready package with thorough test coverage and documentation.

![Code Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen)

---

## Requirements

- PHP ^8.4
- mossy2100/core

---

## Installation

```bash
composer require mossy2100/math
```

---

## Quick start

```php
use Mossy2100\Math\Complex;
use Mossy2100\Math\Rational;

$z = new Complex(3, 4);
echo $z->mul(new Complex(1, -2));    // 11 - 2i
echo $z->magnitude;                  // 5

$r = Rational::fromString('3/4');
echo $r->add(new Rational(1, 6));    // 11/12
```

---

## Strict Typing

Strict typing (`declare(strict_types=1)`) is used throughout the library, with type hints on every property, parameter,
and return type. This catches type errors at the call site instead of letting them surface as subtle bugs later.

One PHP-level exception applies: an `int` is always accepted where a `float` is type-hinted, even under strict types,
since PHP treats this as lossless "widening" (the reverse - passing a `float` where `int` is expected - is not allowed,
and throws a `TypeError`). Many methods here are typed `float` for exactly this reason, so both forms work:

```php
$v = Vector::fromArray([1, 2, 3]);
$v->mul(3);      // int - accepted, widens to 3.0
$v->mul(3.0);    // float - also fine
```

See the "Strict typing" section of the [PHP manual's Type Declarations page](https://www.php.net/manual/en/language.types.declarations.php) for the full rules.

---

## Infinity, NAN and Overflow

Unlike PHP's own float arithmetic, these classes never accept or return `INF`, `-INF` or `NAN`. Instead, they throw:

| Case                                                                | Exception             |
| ------------------------------------------------------------------- | --------------------- |
| A non-finite argument                                               | `DomainException`     |
| A result, or an intermediate value, too large to represent          | `OverflowException`   |
| An undefined operation, such as division by zero or the log of zero | `ArithmeticException` |

A `NAN` result from finite operands always comes from an intermediate value overflowing (e.g. `INF - INF`), so it's
reported as an overflow too. An overflow message names the operation you called, e.g. "Cannot raise the given Complex to
the given power. The result would overflow."

Unlike PHP's `int` arithmetic, a `Rational` result that overflows isn't widened to a float. It throws
`OverflowException`.

---

## Classes

### [Complex](docs/Complex.md)

Immutable class for complex numbers (a + bi) with support for:

- Basic arithmetic operations (add, subtract, multiply, divide)
- Transcendental functions (exp, ln, log, pow, roots)
- Trigonometric and hyperbolic functions (sin, cos, tan, asin, acos, atan, etc.)
- Polar and rectangular form conversions
- Exact and approximate equality comparison
- String parsing and formatting

### [Rational](docs/Rational.md)

Immutable class for rational numbers (p/q) with support for:

- Exact arithmetic using integer ratios (no floating-point errors)
- Automatic reduction to simplest form (e.g., 6/8 → 3/4)
- A dedicated `fromFloat()` method for approximate conversion from floats using continued fractions
- Overflow-safe integer operations
- Exact comparison with `Rational`, `int` and `float` values
- Remainders with `mod()`, with the sign of the dividend, like PHP's `%`
- String parsing and formatting

### [Vector](docs/Vector.md)

Mutable numeric vector with support for:

- Arithmetic: add, subtract, and multiply or divide by a scalar
- Element-wise (Hadamard) product, division and inverse via `hadamardMul()`, `hadamardDiv()` and `hadamardInv()`
- Dot, cross and outer products
- Exact and approximate equality comparison
- Conversion to arrays and matrices
- Array-style element access via the `ArrayAccess` interface
- Iteration over the elements with `foreach`
- String representation using mathematical angle brackets

### [Matrix](docs/Matrix.md)

Mutable two-dimensional matrix with support for:

- Matrix arithmetic (add, subtract, multiply, divide by a scalar)
- Element-wise (Hadamard) product, division and inverse via `hadamardMul()`, `hadamardDiv()` and `hadamardInv()`
- Matrix-vector multiplication using column vector convention
- Transpose, determinant, and inverse operations
- Matrix power with binary exponentiation (including negative powers)
- Row-level `ArrayAccess` interface (get/set rows as Vectors)
- Iteration over the rows with `foreach`
- String representation using box-drawing characters

---

## [Globals](docs/Globals.md)

`M_I`, the imaginary unit constant (namespaced to `Mossy2100\Math`), which is more convenient to use as a global than as
a class member.

---

## Testing

The library includes comprehensive test coverage. `PHP_INI_SCAN_DIR=` stops PHP loading extra ini files, so the tests
run against this package even if the Math extension is enabled.

```bash
# Run all tests
PHP_INI_SCAN_DIR= vendor/bin/phpunit

# Run tests for specific class
PHP_INI_SCAN_DIR= vendor/bin/phpunit tests/Complex
PHP_INI_SCAN_DIR= vendor/bin/phpunit tests/Rational
PHP_INI_SCAN_DIR= vendor/bin/phpunit tests/Vector
PHP_INI_SCAN_DIR= vendor/bin/phpunit tests/Matrix

# Run with coverage (generates HTML report and clover.xml)
composer test
```

---

## License

MIT License - see [LICENSE](LICENSE) for details

---

## Support

- **Issues**: https://github.com/mossy2100/PHP-Math/issues
- **Documentation**: See [docs/](docs/) directory for detailed class documentation
- **Examples**: See test files for comprehensive usage examples

For questions or suggestions, please [open an issue](https://github.com/mossy2100/PHP-Math/issues).

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and changes.
