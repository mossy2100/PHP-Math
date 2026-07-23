<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use OceanMoon\Math\Matrix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixAggregationTest extends TestCase
{
    #region Method count() tests.

    /**
     * Test count() returns the total number of elements (rows * columns).
     */
    public function testCount(): void
    {
        $m = new Matrix(2, 3);
        $this->assertSame(6, $m->count());
    }

    /**
     * Test count() with a square matrix.
     */
    public function testCountSquare(): void
    {
        $m = new Matrix(4, 4);
        $this->assertSame(16, $m->count());
    }

    /**
     * Test count() with a zero-row or zero-column matrix is zero.
     */
    public function testCountWithZeroDimension(): void
    {
        $this->assertSame(0, new Matrix(0, 0)->count());
        $this->assertSame(0, new Matrix(3, 0)->count());
        $this->assertSame(0, new Matrix(0, 3)->count());
    }

    /**
     * Test the global count() function works via the Countable interface.
     */
    public function testGlobalCountFunction(): void
    {
        $m = new Matrix(2, 5);
        $this->assertCount(10, $m);
        $this->assertSame(10, count($m));
    }

    #endregion
}
