<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Vector;

use OceanMoon\Math\Vector;
use OutOfRangeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Vector::class)]
class VectorInspectionTest extends TestCase
{
    #region Method get() tests.

    /**
     * Test get() returns the correct element value.
     */
    public function testGetReturnsCorrectValue(): void
    {
        $v = Vector::fromArray([10, 20, 30]);
        $this->assertSame(10.0, $v->get(0));
        $this->assertSame(20.0, $v->get(1));
        $this->assertSame(30.0, $v->get(2));
    }

    /**
     * Test get() with a negative index throws OutOfRangeException.
     */
    public function testGetWithNegativeIndexThrows(): void
    {
        $v = Vector::fromArray([10, 20, 30]);
        $this->expectException(OutOfRangeException::class);
        $v->get(-1);
    }

    /**
     * Test get() with an index beyond the last element throws OutOfRangeException.
     */
    public function testGetWithIndexBeyondSizeThrows(): void
    {
        $v = Vector::fromArray([10, 20, 30]);
        $this->expectException(OutOfRangeException::class);
        $v->get(3);
    }

    /**
     * Test get() on an empty vector throws OutOfRangeException.
     */
    public function testGetOnEmptyVectorThrows(): void
    {
        $v = new Vector(0);
        $this->expectException(OutOfRangeException::class);
        $v->get(0);
    }

    #endregion
}
