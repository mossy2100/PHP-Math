# Comparison Operators for Math types

> **TL;DR: Don't use PHP's built-in comparison operators with these types.**

Comparison operators for the types provided by this package work according to the default way that PHP implements comparison operators for objects.

1. With **PHP's default comparison operators** (`==`, `!=`, `<=>`, `<`, `<=`, `>`, `>=`) the objects' declared properties are compared.
	1. For the **equality operators** (`==` and `!=`), two objects are equal when they are of the same class and all their properties are equal, regardless of the order in which those properties are declared.
	2. For the **ordering operators** (`<=>`, `<`, `<=`, `>`, `>=`), the properties are compared one after the other in the order they are declared in the class, and the first difference determines the result.
2. With the **strict comparison operators** (`===`, `!==`) the two objects are compared for identity, i.e. `===` will only return `true` if both operands refer to the same instance.

This works fine for `==`, `!=`, `===`, and `!==` when both operands are of the same type. No type conversion occurs.

---

## Property Comparison Order

The properties are ordered and thus compared as follows:

### Complex

1. `float $real`
2. `float $imaginary`
3. `float $magnitude`
4. `float $phase`

The default object comparison algorithm used by PHP includes all backed properties, which is why the `Complex` type's `$magnitude` and `$phase` properties were changed from lazily-computed, cached properties to regular properties computed on object creation. The former implementation meant that two `Complex` values with equal real and imaginary parts would compare as unequal if one had a computed magnitude and one did not.

The updated implementation means `$magnitude` and `$phase` are always computed, and therefore if two `Complex` values have the same values for the `$real` and `$imaginary` properties, then their `$magnitude` and `$phase` properties will also be equal and the objects will compare as equal.

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

## Loose equality operators (`==` and `!=`)

The default implementation means that the `==` and `!=` operators work for all types as expected.

**Example:**

```php
$z1 = new Complex(3, 4);
$z2 = new Complex(5, 6);
var_export($z1 == $z2);
// Prints "false".
```

If the other operand is a scalar, such as an `int` or `float`, PHP will try to convert the object to the scalar's type and emit a notice.

**Example:**

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

Note that the operation still returns a value (in this case, `false`, because the types are different) and the code will continue. If notices are suppressed, which is common, especially in production environments, a bug could go unnoticed. So: be aware.

(Comparing two objects of different classes will return `false` for `==` *without* any notice; no type conversion is attempted.)

The other problem with the above example, as you may have noticed, is that a complex number with a real part of 5 and no imaginary part should, in fact, compare as equal to 5, meaning the result of the operation should have been `true`, in a strict mathematical sense.

The *other* problem with these operators is that several modern coding standards either forbid or discourage their use. This includes:

- **Forbidden** by the [Slevomat coding standard](https://github.com/slevomat/coding-standard/blob/master/README.md) (via its `DisallowEqualOperators` sniff), [PHPStan Strict Rules](https://github.com/phpstan/phpstan-strict-rules) (via the `disallowedLooseComparison` rule), and the [coding standard for Nextcloud](https://docs.nextcloud.com/server/latest/developer_manual/getting_started/coding_standards/php.html#operators).
- **Discouraged** by the coding standards for [Symfony](https://symfony.com/doc/current/contributing/code/standards.html#structure) and [WordPress](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/#clever-code).

Furthermore, the PhpStorm plugin **Php Inspections (EA Extended)** includes an inspection for "Type unsafe comparison", which will complain about `==` and `!=` operators until you disable it.

IN A NUTSHELL: If you want an easier life as a PHP developer, avoid using `==` and `!=`.

The solution in this package is to use the `equal()` or `approxEqual()` methods, which will work with compatible types. For `Complex` and `Rational`, this includes `int` and `float`. The `approxEqual()` method is particularly useful where floating point rounding errors can be a problem.

---

## Ordering operators (`<=>`, `<`, `<=`, `>`, and `>=`)

Because PHP provides a default way of comparing objects (as explained earlier), these operations will execute without triggering a notice, warning, or error, as long as the operands are of the same type. If not, a notice will be triggered, as shown above.

> **They should not be used.**

Firstly, the `Complex`, `Vector`, and `Matrix` types have no natural or accepted sort order, making ordering comparisons undefined and invalid.

Secondly, for `Rational` values, the default comparison algorithm simply doesn't work. PHP will compare the numerators first, then the denominators.

**Example**

```php
$r1 = new Rational(1, 2); // 1/2
$r2 = new Rational(1, 3); // 1/3
var_export($r1 > $r2);
// Prints "false"
```

Of course, 1/2 is greater than 1/3, so the result of this comparison should have been `true`.

Instead, use the following methods. In the table below, `$r` is a `Rational`, and `$x` can be a `Rational`, `int`, or `float`.

| Desired operation | Method                       |
| ----------------- | ---------------------------- |
| `$r <=> $x`       | `$r->compare($x)`            |
| `$r < $x`         | `$r->lessThan($x)`           |
| `$r <= $x`        | `$r->lessThanOrEqual($x)`    |
| `$r > $x`         | `$r->greaterThan($x)`        |
| `$r >= $x`        | `$r->greaterThanOrEqual($x)` |

---

## Overloading Operators

The [OceanMoon Math extension](https://github.com/mossy2100/PHP-Math-extension) provides a PHP extension that replicates this package, with the addition of operators. This enhances the utility of the types and readability of code, while also providing a substantial performance boost.

All types get a useful set of arithmetic operators. `Complex` and `Rational` also get comparison operators, overriding the default behaviour described above.

### Complex

Overloading comparison operators for `Complex` means that `Complex` values can be compared for equality with `int` and `float` values, producing the correct result for `==` and `!=` operations without triggering notices.

The ordering operators behave as usual, but, as explained, shouldn't generally be used.

See: [Complex Operators](https://github.com/mossy2100/PHP-Math-extension/blob/main/docs/Complex.md#comparison-operators).

### Rational

Overloading comparison operators for `Rational` means that `Rational` values can be compared with `Rational`, `int` and `float` values, including equality and ordering comparisons, producing the correct result without triggering notices.

See: [Rational Operators](https://github.com/mossy2100/PHP-Math-extension/blob/main/docs/Rational.md#comparison-operators).

---

## See Also

- [Complex - Comparison Methods](Complex.md#comparison-methods)
- [Rational - Comparison Methods](Rational.md#comparison-methods)
- [Vector - Comparison Methods](Vector.md#comparison-methods)
- [Matrix - Comparison Methods](Matrix.md#comparison-methods)
