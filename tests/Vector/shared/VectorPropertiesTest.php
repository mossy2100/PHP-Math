<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Vector;

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

    #endregion
}
