<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Vector;

use OceanMoon\Math\Vector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Vector::class)]
class VectorAggregationTest extends TestCase
{
    #region Method sum() tests.

    /**
     * Test sum of a vector's elements.
     */
    public function testSum(): void
    {
        $v = Vector::fromArray([1, 2, 3, 4]);
        $this->assertEqualsWithDelta(10.0, $v->sum(), EPSILON);
    }

    /**
     * Test sum of an empty vector is 0 (the additive identity).
     */
    public function testSumWithEmptyVector(): void
    {
        $v = new Vector(0);
        $this->assertSame(0.0, $v->sum());
    }

    #endregion

    #region Method prod() tests.

    /**
     * Test product of a vector's elements.
     */
    public function testProd(): void
    {
        $v = Vector::fromArray([1, 2, 3, 4]);
        $this->assertEqualsWithDelta(24.0, $v->prod(), EPSILON);
    }

    /**
     * Test product of a vector containing a zero element is 0.
     */
    public function testProdWithZeroElement(): void
    {
        $v = Vector::fromArray([1, 2, 0, 4]);
        $this->assertSame(0.0, $v->prod());
    }

    /**
     * Test product of an empty vector is 1 (the multiplicative identity).
     */
    public function testProdWithEmptyVector(): void
    {
        $v = new Vector(0);
        $this->assertSame(1.0, $v->prod());
    }

    #endregion

    #region Method count() tests.

    /**
     * Test count() matches size for a non-empty vector.
     */
    public function testCount(): void
    {
        $v = new Vector(5);
        $this->assertSame(5, $v->count());
        $this->assertSame($v->size, $v->count());
    }

    /**
     * Test count() with an empty vector.
     */
    public function testCountWithEmptyVector(): void
    {
        $v = new Vector(0);
        $this->assertSame(0, $v->count());
    }

    /**
     * Test the global count() function works via the Countable interface.
     */
    public function testGlobalCountFunction(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $this->assertCount(3, $v);
        $this->assertSame(3, count($v));
    }

    #endregion
}
