# OceanMoon PHP Math package

Provides classes for Complex numbers, Rational numbers, Vectors, and Matrices.

**[License](LICENSE)** | **[Changelog](CHANGELOG.md)** | **[Documentation](docs/)**

![PHP 8.4](docs/logo_php8_4.png)

---

## Description

This package provides classes for working with complex numbers, rational numbers, vectors, and matrices in PHP.

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

[Claude Chat](https://claude.ai) and [Claude Code](https://www.claude.com/product/claude-code) were used in the
development of this package. The core classes were designed, coded, and commented primarily by the author, with Claude
providing substantial assistance with code review, suggesting improvements, debugging, and generating tests and
documentation. All code was thoroughly reviewed by the author, and validated using industry-standard tools including
[PHP_Codesniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer/), [PHPStan](https://phpstan.org/) (to level 9), and
[PHPUnit](https://phpunit.de/index.html) to ensure full compliance with [PSR-12](https://www.php-fig.org/psr/psr-12/)
coding standards and comprehensive unit testing with 100% code coverage. This collaborative approach has produced a
well-designed, production-ready package with thorough test coverage and documentation.

![Code Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen)

---

## Requirements

- PHP ^8.4
- oceanmoon/core

---

## Installation

```bash
composer require oceanmoon/math
```

---

## Strict Typing

Strict typing (`declare(strict_types=1)`) is used throughout the library, with type hints on every property, parameter,
and return type - this catches type errors at the call site instead of them surfacing as subtle bugs later.

One PHP-level exception applies: an `int` is always accepted where a `float` is type-hinted, even under strict types,
since PHP treats this as lossless "widening" (the reverse - passing a `float` where `int` is expected - is not allowed,
and throws a `TypeError`). Many methods here are typed `float` for exactly this reason, so both forms work:

```php
$v = Vector::fromArray([1, 2, 3]);
$v->mul(3);      // int - accepted, widens to 3.0
$v->mul(3.0);    // float - also fine
```

See the ["Strict typing" section](https://www.php.net/manual/en/language.types.declarations.php) of the PHP manual's
Type Declarations page for the full rules.

---

## Classes

### [Complex](docs/Complex.md)

Immutable class for complex numbers (a + bi) with support for:

- Basic arithmetic operations (add, subtract, multiply, divide)
- Transcendental functions (exp, ln, log, pow, roots)
- Trigonometric and hyperbolic functions (sin, cos, tan, asin, acos, atan, etc.)
- Polar and rectangular form conversions
- Epsilon-based equality comparison
- String parsing and formatting

### [Rational](docs/Rational.md)

Immutable class for rational numbers (p/q) with support for:

- Exact arithmetic using integer ratios (no floating-point errors)
- Automatic reduction to simplest form (e.g., 6/8 → 3/4)
- A dedicated `fromFloat()` method for approximate conversion from floats using continued fractions
- Overflow-safe integer operations
- Comparison operations with mixed types
- String parsing and formatting

### [Vector](docs/Vector.md)

Mutable numeric vector with support for:

- Element-wise arithmetic (add, subtract, scalar multiply, scalar divide)
- Dot, cross, Hadamard, and outer product operations
- Exact and approximate equality comparison
- Conversion to arrays and matrices
- Array-style element access via the `ArrayAccess` interface
- String representation using mathematical angle brackets

### [Matrix](docs/Matrix.md)

Mutable two-dimensional matrix with support for:

- Matrix arithmetic (add, subtract, multiply, divide)
- Matrix-vector multiplication using column vector convention
- Transpose, determinant, and inverse operations
- Matrix power with binary exponentiation (including negative powers)
- Row-level `ArrayAccess` interface (get/set rows as Vectors)
- String representation using box-drawing characters

---

## Constants

### [constants.php](src/constants.php)

Composer's PSR-4 autoloading only handles classes — a file that declares global constants or functions has nothing for
it to load on demand. To solve this, a dedicated file `constants.php` is loaded from the `bootstrap.php` file whenever
the Math extension is not present. The `bootstrap.php` file is registered via Composer's
[`files` autoload](https://getcomposer.org/doc/04-schema.md#files) mechanism, which includes it unconditionally whenever
the package is loaded.

- **`M_I`** - Representing the imaginary unit `i` as a `Complex(0, 1)` instance. Import it with
  `use const OceanMoon\Math\M_I;`. This is not a class constant because PHP only permits object constants to be created
  using the `const` keyword (not `define`) outside of a class.

There are no other global constants or functions at this time, but more may be added to this file in the future if
needed.

---

## Testing

The library includes comprehensive test coverage:

```bash
# Run all tests
vendor/bin/phpunit

# Run tests for specific class
vendor/bin/phpunit tests/Complex
vendor/bin/phpunit tests/Rational
vendor/bin/phpunit tests/Vector
vendor/bin/phpunit tests/Matrix

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
