<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use InvalidArgumentException;
use LengthException;
use LogicException;
use OceanMoon\Math\Matrix;
use OceanMoon\Math\Vector;
use OutOfRangeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixArrayAccessTest extends TestCase
{
    #region Method offsetExists() tests.

    /**
     * Test offsetExists with a valid index.
     */
    public function testOffsetExistsWithValidIndex(): void
    {
        $m = new Matrix(2, 3);

        $this->assertTrue(isset($m[0]));
        $this->assertTrue(isset($m[1]));
    }

    /**
     * Test offsetExists with an out-of-range index.
     */
    public function testOffsetExistsWithOutOfRangeIndex(): void
    {
        $m = new Matrix(2, 3);

        $this->assertFalse(isset($m[2]));
    }

    /**
     * Test offsetExists with a negative index.
     */
    public function testOffsetExistsWithNegativeIndex(): void
    {
        $m = new Matrix(2, 3);

        $this->assertFalse(isset($m[-1]));
    }

    #endregion

    #region Method offsetGet() tests.

    /**
     * Test offsetGet (single index) returns a Vector for the row.
     */
    public function testOffsetGetReturnsVector(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);

        $this->assertInstanceOf(Vector::class, $m[0]);
        $this->assertSame([1.0, 2.0, 3.0], $m[0]->toArray());
        $this->assertSame([4.0, 5.0, 6.0], $m[1]->toArray());
    }

    /**
     * Test offsetGet (double index) reads the correct element.
     */
    public function testOffsetGetDoubleIndex(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);

        $this->assertSame(1.0, $m[0][0]);
        $this->assertSame(6.0, $m[1][2]);
    }

    /**
     * Test offsetGet with a non-int offset throws InvalidArgumentException.
     */
    public function testOffsetGetNonIntegerOffsetThrows(): void
    {
        $m = new Matrix(2, 3);

        $this->expectException(InvalidArgumentException::class);
        $row = $m['row'];
    }

    /**
     * Test offsetGet with an out-of-range offset throws OutOfRangeException.
     */
    public function testOffsetGetOutOfRangeThrows(): void
    {
        $m = new Matrix(2, 3);

        $this->expectException(OutOfRangeException::class);
        $row = $m[2];
    }

    #endregion

    #region Method offsetSet() tests.

    /**
     * Test offsetSet (single index) sets the whole row.
     */
    public function testOffsetSetSingleIndex(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $m[1] = Vector::fromArray([10, 11, 12]);

        $this->assertSame([1.0, 2.0, 3.0], $m->getRow(0)->toArray());
        $this->assertSame([10.0, 11.0, 12.0], $m->getRow(1)->toArray());
    }

    /**
     * Test offsetSet (double index) mutates the Matrix in place.
     */
    public function testOffsetSetDoubleIndex(): void
    {
        $m = new Matrix(2, 3);
        $m[0][1] = 42;

        $this->assertSame(42.0, $m->get(0, 1));
    }

    /**
     * Test offsetSet with a non-Vector value throws InvalidArgumentException.
     */
    public function testOffsetSetNonVectorValueThrows(): void
    {
        $m = new Matrix(2, 3);

        $this->expectException(InvalidArgumentException::class);
        $m[0] = [1, 2, 3];
    }

    /**
     * Test offsetSet with a wrong-length Vector throws LengthException.
     */
    public function testOffsetSetWrongLengthVectorThrows(): void
    {
        $m = new Matrix(2, 3);

        $this->expectException(LengthException::class);
        $m[0] = Vector::fromArray([1, 2]);
    }

    #endregion

    #region Method offsetUnset() tests.

    /**
     * Test offsetUnset throws LogicException.
     */
    public function testOffsetUnsetThrows(): void
    {
        $m = new Matrix(2, 3);

        $this->expectException(LogicException::class);
        unset($m[0]);
    }

    #endregion

    #region Live reference vs. independent copy tests.

    /**
     * Test $m[$row] returns a live reference: mutating it mutates the Matrix, unlike getRow(), which returns an
     * independent copy.
     */
    public function testOffsetGetReturnsLiveReference(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);

        // $m[$row] is live: mutating it mutates the Matrix.
        $liveRow = $m[0];
        $liveRow->set(0, 999);
        $this->assertSame(999.0, $m->get(0, 0));

        // getRow() is a copy: mutating it does not mutate the Matrix.
        $copiedRow = $m->getRow(1);
        $copiedRow->set(0, 999);
        $this->assertSame(4.0, $m->get(1, 0));
    }

    /**
     * Test a live reference obtained via $m[$row] stays valid and up to date after setRow(): setRow() mutates the
     * row's existing Vector in place rather than replacing it.
     */
    public function testLiveReferenceStaysValidAfterSetRow(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $liveRow = $m[0];

        $m->setRow(0, Vector::fromArray([7, 8, 9]));

        $this->assertSame([7.0, 8.0, 9.0], $liveRow->toArray());
    }

    #endregion
}
