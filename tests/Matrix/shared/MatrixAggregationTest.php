<?php

declare(strict_types=1);

namespace Mossy2100\Math\Tests\Matrix;

use Mossy2100\Math\Matrix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixAggregationTest extends TestCase
{
    #region Method count() tests.

    /**
     * Test count() returns the number of rows.
     */
    public function testCount(): void
    {
        $m = new Matrix(2, 3);
        $this->assertSame(2, $m->count());
    }

    /**
     * Test count() with a square matrix.
     */
    public function testCountSquare(): void
    {
        $m = new Matrix(4, 4);
        $this->assertSame(4, $m->count());
    }

    /**
     * Test count() with a zero-row or zero-column matrix is the number of rows.
     */
    public function testCountWithZeroDimension(): void
    {
        $this->assertSame(0, new Matrix(0, 0)->count());
        $this->assertSame(3, new Matrix(3, 0)->count());
        $this->assertSame(0, new Matrix(0, 3)->count());
    }

    /**
     * Test the global count() function works via the Countable interface.
     */
    public function testGlobalCountFunction(): void
    {
        $m = new Matrix(2, 5);
        $this->assertCount(2, $m);
        $this->assertSame(2, count($m));
    }

    /**
     * Test count() matches the row indexes and foreach, so a for loop over count() visits every row.
     */
    public function testCountMatchesRowIndexesAndForeach(): void
    {
        $m = Matrix::fromArray([[1, 2, 3], [4, 5, 6]]);

        $rows = [];
        for ($i = 0; $i < count($m); $i++) {
            $rows[] = $m[$i]->toArray();
        }

        $this->assertSame($m->toArray(), $rows);
        $this->assertCount(count($m), iterator_to_array($m));
    }

    #endregion
}
