<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Vector;

use Error;
use OceanMoon\Core\Traits\Asserts\FloatAssertions;
use OceanMoon\Math\Vector;
use OverflowException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Vector::class)]
class VectorPropertiesTest extends TestCase
{
    use FloatAssertions;

    #region Property $magnitude tests.

    /**
     * Test magnitude of a [3, 4] vector equals 5.
     */
    public function testMagnitudeThreeFourEqualsFive(): void
    {
        $v = Vector::fromArray([3, 4]);
        $this->assertSame(5.0, $v->magnitude);
    }

    /**
     * Test magnitude of an empty vector equals zero.
     */
    public function testMagnitudeEmptyVectorEqualsZero(): void
    {
        $v = new Vector(0);
        $this->assertSame(0.0, $v->magnitude);
    }

    /**
     * Test magnitude of a single-element vector equals the absolute value of the element.
     */
    public function testMagnitudeSingleElement(): void
    {
        $v = Vector::fromArray([1]);
        $this->assertSame(1.0, $v->magnitude);
    }

    /**
     * Test that magnitude is not cached: it reflects the vector's current elements after mutation.
     */
    public function testMagnitudeNotCached(): void
    {
        $v = Vector::fromArray([3, 4]);
        $this->assertSame(5.0, $v->magnitude);

        $v->set(0, 0);
        $v->set(1, 12);
        $this->assertSame(12.0, $v->magnitude);
    }

    /**
     * Data for vectors whose elements would overflow or underflow if squared without scaling.
     *
     * @return array<string, array{float, float, float}> The two vector elements, expected magnitude.
     */
    public static function magnitudeExtremeProvider(): array
    {
        return [
            'huge'                   => [3e300, 4e300, 5e300],
            'huge, squares overflow' => [1e308, 1e308, 1.4142135623730951e308],
            'tiny'                   => [3e-200, -4e-200, 5e-200],
            'subnormal'              => [0.0, 5e-324, 5e-324],
        ];
    }

    /**
     * Test magnitude is accurate for a vector whose elements would overflow or underflow if squared.
     *
     * @param float $x The first vector element.
     * @param float $y The second vector element.
     * @param float $expected The expected magnitude.
     */
    #[DataProvider('magnitudeExtremeProvider')]
    public function testMagnitudeExtreme(float $x, float $y, float $expected): void
    {
        // Use a zero absolute tolerance, so a magnitude that wrongly underflows to 0 fails.
        $this->assertApproxEqual($expected, Vector::fromArray([$x, $y])->magnitude, absTol: 0.0);
    }

    /**
     * Test magnitude throws when the magnitude itself is too large to represent.
     */
    public function testMagnitudeOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot compute magnitude of the given Vector. The result would overflow.');
        $magnitude = Vector::fromArray([1.5e308, 1.5e308])->magnitude;
    }

    /**
     * Test that magnitude is read-only: it has no setter, so assigning to it throws Error.
     */
    public function testMagnitudeIsReadOnly(): void
    {
        $v = Vector::fromArray([3, 4]);

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Property ' . Vector::class . '::$magnitude is read-only');
        // This is a general ignore rather than error-specific, because the package and extension produce different
        // error identifiers here (assign.propertyReadOnly vs. assign.propertyProtectedSet), due to the extension
        // build process not understanding property hook syntax in stubs (Vector::$magnitude is declared as a
        // `readonly` property there instead, to match the package's read-only behavior as closely as possible).
        // @phpstan-ignore-next-line
        $v->magnitude = 99;
    }

    /**
     * Test that isset() and empty() compute magnitude, as for any property with a value.
     */
    public function testMagnitudeIssetAndEmpty(): void
    {
        $v = Vector::fromArray([3, 4]);
        $zero = Vector::fromArray([0, 0]);

        $this->assertTrue(self::issetMagnitude($v));
        $this->assertFalse(empty($v->magnitude));
        $this->assertTrue(empty($zero->magnitude));
    }

    /**
     * Test that json_encode(), get_object_vars() and var_export() include magnitude.
     */
    public function testMagnitudeInPublicProperties(): void
    {
        $v = Vector::fromArray([3, 4]);

        $this->assertSame('{"count":2,"magnitude":5}', json_encode($v));
        $this->assertSame([
            'count'     => 2,
            'magnitude' => 5.0,
        ], get_object_vars($v));
        $this->assertStringContainsString("'magnitude' => 5.0", var_export($v, true));
    }

    /**
     * Test that print_r() and an (array) cast leave magnitude out, since it has no stored value.
     */
    public function testMagnitudeNotInStoredProperties(): void
    {
        $v = Vector::fromArray([3, 4]);

        $this->assertStringNotContainsString('magnitude', print_r($v, true));
        $this->assertArrayNotHasKey('magnitude', (array) $v);
    }

    #endregion

    #region Helper methods

    /**
     * Check isset() on magnitude. The parameter is a plain object because the package's and the extension's PHPStan
     * disagree on whether isset() on the property is always true, and a shared test can't ignore an error only one of
     * them reports.
     *
     * @param object $v The Vector.
     * @return bool The result of isset().
     */
    private static function issetMagnitude(object $v): bool
    {
        return isset($v->magnitude);
    }

    #endregion
}
