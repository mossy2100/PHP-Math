<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Vector;

use Error;
use OceanMoon\Math\Vector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Vector::class)]
class VectorPropertiesTest extends TestCase
{
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

    #endregion
}
