<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use ArrayIterator;
use OceanMoon\Math\Matrix;
use OceanMoon\Math\Vector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixIteratorAggregateTest extends TestCase
{
    #region Method getIterator() tests.

    /**
     * Test foreach yields the row Vectors with their indexes.
     */
    public function testForeachYieldsRows(): void
    {
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
            [5, 6],
        ]);

        $result = [];
        foreach ($m as $index => $row) {
            $this->assertInstanceOf(Vector::class, $row);
            $result[$index] = $row->toArray();
        }

        $this->assertSame($m->toArray(), $result);
    }

    /**
     * Test getIterator() returns an ArrayIterator over the same row objects as offsetGet().
     */
    public function testGetIteratorYieldsLiveRows(): void
    {
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $rows = iterator_to_array($m);

        $this->assertInstanceOf(ArrayIterator::class, $m->getIterator());
        $this->assertSame($m[0], $rows[0]);
        $this->assertSame($m[1], $rows[1]);
    }

    /**
     * Test changing a row in the loop changes the Matrix.
     */
    public function testChangingRowInLoopChangesMatrix(): void
    {
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);

        foreach ($m as $index => $row) {
            $row[0] = 10 * ($index + 1);
        }

        $this->assertSame([
            [10.0, 2.0],
            [20.0, 4.0],
        ], $m->toArray());
    }

    /**
     * Test iterating an empty Matrix yields nothing.
     */
    public function testIterateEmpty(): void
    {
        $this->assertSame([], iterator_to_array(new Matrix(0, 0)));
    }

    #endregion
}
