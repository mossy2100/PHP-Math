# Comparison Operators for Math Types

## Summary

**NB: It is generally not recommended to use comparison operators with these types.**

Comparison operators for the types provided by this package work according to the default way that PHP implements
comparison operators for objects. There's no way to override operator behaviour in a package.

Several [methods](#comparison-methods) are provided to support comparisons, which should be preferred.

This document exists to explain which comparison operations will work correctly, and when. It also exists in part as a
justification for the [Math extension](https://github.com/mossy2100/PHP-Math-extension), which overloads operators for
these types.

---

## Loose Equality (`==` and `!=`)

### Comparing two objects of the same type

In PHP, two objects are considered equal when they are of the same type and all their backed properties are equal.

**Example:**

```php
$z1 = new Complex(3, 4);
$z2 = new Complex(5, 6);
var_export($z1 == $z2);
// Prints "false".
```

### Comparing two objects of different types

Because the types are different, the operation will return `false` for `==` (or, conversely, `true` for `!=`), without
triggering a notice; no type conversion is attempted, and no property values will be considered.

### Comparing an object with a scalar

Behaviour depends on the operand type — there's no single rule:

| Operand type              | Behaviour                                                                                                                                 |
| ------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| `int`, `float`            | PHP tries to convert the object to the scalar type, which emits a notice; the object converts to `1` regardless of its actual properties. |
| `bool`                    | No conversion attempted, no notice. **Any object loosely equals `true`**, and never equals `false` — a silent, dangerous gotcha.          |
| `null`, `string`, `array` | No conversion attempted, no notice. Always compares as **not equal**, even against a numeric string or an empty array.                    |

**Example** (the `int`/`float` case):

```php
$z = new Complex(5);
var_export($z == 5);
```

Prints:

```
Notice: Object of class OceanMoon\Math\Complex could not be converted to int in /path/example.php on line 19

Call Stack:
    0.0002 472080 1. {main}() /path/example.php:0

false
```

The operation still returns `false` and the code will continue. If notices are suppressed, which is common, especially
in production environments, a bug could go unnoticed. So: be aware.

This might be ok with some types, but when it comes to numbers, we get behaviour that is arguably incorrect, because,
unlike when comparing an `int` with a `float`, **no type coercion occurs**.

**However, with number types, sometimes we actually want type coercion.**

The above example illustrates a problem specific to the `Complex` and `Rational` types. A complex number with a real
part of 5 and no imaginary part _should_, in fact, compare as equal to 5, meaning the result of the operation should
have been `true`, in a strict mathematical sense.

**Example 2:**

```php
$z = new Rational(1, 2);
var_export($z == 0.5);
```

Again, a notice will be issued and the result will be `false`, even though technically it should have been `true`. (This
is unrelated to floating point rounding issues; 0.5 can be represented exactly as a `float`.)

My [Math extension](https://github.com/mossy2100/PHP-Math-extension), which replicates this package **and** adds
operators, solves this problem by overloading comparison operators for `Complex` and `Rational`, which enables these
types to be compared correctly for equality with numbers.

### Coding Standards

Another problem with these operators is that several modern coding standards either forbid or discourage their use.

This includes:

- **Forbidden** by the [Slevomat coding standard](https://github.com/slevomat/coding-standard/blob/master/README.md)
  (via its `DisallowEqualOperators` sniff), [PHPStan Strict Rules](https://github.com/phpstan/phpstan-strict-rules) (via
  the `disallowedLooseComparison` rule), and the
  [coding standard for Nextcloud](https://docs.nextcloud.com/server/latest/developer_manual/getting_started/coding_standards/php.html#operators).
- **Discouraged** by the coding standards for
  [Symfony](https://symfony.com/doc/current/contributing/code/standards.html#structure) and
  [WordPress](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/#clever-code).

Furthermore, the PhpStorm plugin **Php Inspections (EA Extended)** includes an inspection for "Type unsafe comparison",
which will complain about `==` and `!=` operators.

IN A NUTSHELL: If you want an easier life as a PHP developer, avoid using `==` and `!=`.

The solution in this package is to use the `equal()` or `approxEqual()` methods, which will work with compatible types.
For `Complex` and `Rational`, this includes `int` and `float`. The `approxEqual()` method is particularly useful where
floating point rounding errors can be a problem.

---

## Ordering Comparison (`<=>`, `<`, `<=`, `>`, and `>=`)

These operations always execute without a notice, warning, or error — **except** against an `int` or `float` operand,
where PHP's attempt to convert the object to that scalar type triggers a notice (as above).

These comparisons have no defined meaning for `Complex`, `Vector`, and `Matrix`, and simply do not work correctly for
`Rational` (the reason is explained below). So, **don't use them**.

If you _do_ use them, you will get the following results.

### Comparing two objects of the same type

If the types are the same, the objects' backed properties are compared one after the other in the order they are
declared in the class, and the first difference determines the result.

Therefore, for a `Complex` value, if the first value has a lesser real part than the second, it will compare as "less
than", irrespective of the imaginary parts. If the real parts are equal, _then_ the imaginary parts will be compared.

For `Rational` values, the default comparison algorithm simply doesn't work. PHP will first compare the numerators, then
the denominators.

**Example**

```php
$r1 = new Rational(1, 2); // 1/2
$r2 = new Rational(1, 3); // 1/3
var_export($r1 > $r2);
// Prints "false"
```

Of course, 1/2 is greater than 1/3, so the result of this comparison should have been `true`. The
[Math extension](https://github.com/mossy2100/PHP-Math-extension) fixes this too — for `Rational` values, ordering
comparisons work correctly once its operators are overloaded.

For `Vector` and `Matrix` values, the sizes will be compared first, then the element values.

### Comparing two objects of different types

PHP considers these genuinely incomparable, and the operators disagree with each other about precisely how:

- `<=>` can't express "incomparable" (it must return an `int`), so it always returns `1` — **regardless of operand
  order**. `$complex <=> $rational` and `$rational <=> $complex` both return `1`, which is supposed to mean "greater than". Welcome to PHP.
- `<`, `<=`, `>`, `>=` do **not** derive their result from that `1` the way you'd expect from `<=>`'s usual meaning.
  They're independently always `false`, for both operands and in both directions.

**Example:**

```php
$z = new Complex(5, 0);
$r = new Rational(1, 2);

var_export($z <=> $r); // 1
var_export($r <=> $z); // Also 1 — not the expected -1.
var_export($z > $r);   // false
var_export($z < $r);   // Also false — nothing is "less than" or "greater than" here, despite the 1 above.
```

No notice, warning, or error is triggered for any of this — it fails silently.

### Comparing an object with a scalar

Behaviour depends on the scalar type, and does **not** follow a simple "object is always greater" rule:

| Scalar type    | Behaviour                                                                                                                                                   |
| -------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `null`         | Object casts to `bool` `true`, `null` to `false` — object always compares greater.                                                                          |
| `bool`         | Same rule. An object always casts to `true`, so **`$obj <=> true` is always `0`** (equal), regardless of the object's value.                                |
| `int`, `float` | PHP casts the object via `(int)`/`(float)`, which is always exactly `1` — unrelated to its properties — and triggers a notice. Effectively `1 <=> $scalar`. |
| `string`       | No cast is attempted; the object always compares greater, even against numeric strings.                                                                     |
| `array`        | Also always compares greater — contradicting the commonly-cited "arrays beat everything" rule.                                                              |

The `bool` and `int`/`float` rows are the most dangerous: `$complex <=> true` silently returns `0`, and ordering against
a number is entirely divorced from the object's actual value.

---

## Strict Equality (`===` and `!==`)

**These operators work correctly.**

When using these operators in PHP, two objects are compared for identity, i.e. `===` will only return `true` if both
operands refer to the exact same instance.

This is PHP's default behaviour and cannot be modified by a package or extension.

---

## Math Types Property Order

The properties are ordered and thus compared as follows:

### Complex

1. `float $real`
2. `float $imaginary`
3. `float $magnitude`
4. `float $phase`

### Rational

1. `int $numerator`
2. `int $denominator`

### Vector

1. `int $count`
2. `list<float> $data`

The `Vector` type's `$magnitude` property is virtual (computed on demand) and therefore not used for comparison.

### Matrix

1. `int $rowCount`
2. `int $columnCount`
3. `list<Vector> $data`

---

## Comparison Methods

Instead of operators, use the following methods. In the table below, `$r` is a `Rational`, and `$x` can be a `Rational`,
`int`, or `float`.

| Desired operation | Method                       |
| ----------------- | ---------------------------- |
| `$r <=> $x`       | `$r->compare($x)`            |
| `$r < $x`         | `$r->lessThan($x)`           |
| `$r <= $x`        | `$r->lessThanOrEqual($x)`    |
| `$r > $x`         | `$r->greaterThan($x)`        |
| `$r >= $x`        | `$r->greaterThanOrEqual($x)` |

If you attempt to use these methods with incompatible types, you'll get an `InvalidArgumentException`.

Refer to the class documentation for more details:

- [Complex - Comparison Methods](Complex.md#comparison-methods)
- [Rational - Comparison Methods](Rational.md#comparison-methods)
- [Vector - Comparison Methods](Vector.md#comparison-methods)
- [Matrix - Comparison Methods](Matrix.md#comparison-methods)

---

## Overloading Operators: the Math Extension

The [OceanMoon Math extension](https://github.com/mossy2100/PHP-Math-extension) provides a PHP extension that replicates
this package, with the addition of operators. This enhances the utility of the types and readability of code, while also
providing a substantial performance boost.

All types get a useful set of arithmetic operators. `Complex` and `Rational` also get comparison operators, overriding
the default behaviour described above.

### Complex

Overloading comparison operators for `Complex` means that `Complex` values can be compared for equality with `int` and
`float` values, producing the correct result for `==` and `!=` operations without triggering notices.

See:
[Complex Operators](https://github.com/mossy2100/PHP-Math-extension/blob/main/docs/Complex.md#comparison-operators).

### Rational

Overloading comparison operators for `Rational` means that `Rational` values can be compared with `Rational`, `int` and
`float` values, including equality and ordering comparisons, producing the correct result without triggering notices.

See:
[Rational Operators](https://github.com/mossy2100/PHP-Math-extension/blob/main/docs/Rational.md#comparison-operators).
