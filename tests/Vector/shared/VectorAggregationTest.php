<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Vector;

use OceanMoon\Math\Vector;
use OverflowException;
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

    /**
     * Test sum() throws when the result overflows.
     */
    public function testSumOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot compute sum of the given Vector. The result would overflow.');
        Vector::fromArray([1e308, 1e308])->sum();
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
     * Test a zero product with a negative factor isn't a negative zero. assertSame() treats -0.0 and 0.0 as equal, so
     * compare the strings, where -0.0 is "-0".
     */
    public function testProdHasNoNegativeZero(): void
    {
        $v = Vector::fromArray([0, -3]);
        $this->assertSame('0', strval($v->prod()));
    }

    /**
     * Test product of an empty vector is 1 (the multiplicative identity).
     */
    public function testProdWithEmptyVector(): void
    {
        $v = new Vector(0);
        $this->assertSame(1.0, $v->prod());
    }

    /**
     * Test prod() throws when the result overflows.
     */
    public function testProdOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot compute product of the given Vector. The result would overflow.');
        Vector::fromArray([1e200, 1e200])->prod();
    }

    #endregion

    #region Method count() tests.

    /**
     * Test count() matches the $count property for a non-empty vector.
     */
    public function testCount(): void
    {
        $v = new Vector(5);
        $this->assertSame(5, $v->count());
        $this->assertSame($v->count, $v->count());
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
