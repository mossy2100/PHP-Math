# Constants

Global constants used by the Math package.

---

## Overview

`src/globals.php` provides a small set of namespaced constants (`OceanMoon\Math`) that are more useful as globals than
as class members. The number of items is deliberately kept small to minimise the number of `use const` statements, which
aren't that common or well-known in PHP.

`M_I` is declared with `const` rather than `define()` because it holds a `Complex` object, and PHP only permits object
values in a namespaced global constant when declared with the `const` keyword outside a class.

---

## Autoloading

Since these are namespaced global identifiers rather than class members, PSR-4 autoloading won't discover them
automatically. Instead, `globals.php` is loaded from `src/bootstrap.php`, which is registered via the Math package's
`composer.json` `files` autoload entry, so it's included unconditionally whenever the package is loaded.

`bootstrap.php` only loads `globals.php` conditionally, though: if the Math extension is also loaded, the extension
already registers `M_I` itself, and redeclaring a `const` a second time is a fatal error. `bootstrap.php` checks
`extension_loaded('oceanmoon_math')` first and skips `globals.php` when the extension is present, so the package and
the extension can coexist safely.

To use a constant without qualifying the namespace every time, add a `use const` import:

**Example:**

```php
use const OceanMoon\Math\M_I;
```

---

## Constants

### M_I

```php
const M_I = new Complex(0, 1);
```

The imaginary unit `i`, represented as a [`Complex`](Complex.md) instance with real part `0` and imaginary part `1`. Named to match PHP's own naming pattern for mathematical constants, such as `M_PI`, `M_E`, etc.

```php
use const OceanMoon\Math\M_I;

$result = M_I->pow(2);  // -1 + 0i
```

---

## See Also

- **[Complex](Complex.md)** - Complex number arithmetic; `M_I` is a `Complex` instance representing the imaginary unit.
- **`M_TAU`** - The `OceanMoon\Core\M_TAU` constant (2π), used internally by `Complex::roots()` and `Complex::exp()`. See
  [Core's Globals](https://github.com/mossy2100/PHP-Core/blob/main/docs/Globals.md).
