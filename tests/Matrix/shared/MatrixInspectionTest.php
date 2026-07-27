<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use OceanMoon\Math\Matrix;
use OceanMoon\Math\Vector;
use OutOfRangeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixInspectionTest extends TestCase
{
    #region Method isSquare() tests.

    /**
     * Test isSquare with a square matrix.
     */
    public function testIsSquareWithSquareMatrix(): void
    {
        $mat = new Matrix(3, 3);
        $this->assertTrue($mat->isSquare());
    }

    /**
     * Test isSquare with a non-square matrix.
     */
    public function testIsSquareWithNonSquareMatrix(): void
    {
        $mat = new Matrix(2, 3);
        $this->assertFalse($mat->isSquare());
    }

    /**
     * Test isSquare with a specific size that matches.
     */
    public function testIsSquareWithSpecificSizeMatches(): void
    {
        $mat = new Matrix(3, 3);
        $this->assertTrue($mat->isSquare(3));
    }

    /**
     * Test isSquare with a specific size that does not match.
     */
    public function testIsSquareWithSpecificSizeDoesNotMatch(): void
    {
        $mat = new Matrix(3, 3);
        $this->assertFalse($mat->isSquare(2));
    }

    /**
     * Test isSquare with specific size on a non-square matrix.
     */
    public function testIsSquareWithSpecificSizeOnNonSquareMatrix(): void
    {
        $mat = new Matrix(2, 3);
        $this->assertFalse($mat->isSquare(2));
    }

    #endregion

    #region Method get() tests.

    /**
     * Test getting a valid element.
     */
    public function testGetValidElement(): void
    {
        $mat = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $this->assertSame(1.0, $mat->get(0, 0));
        $this->assertSame(5.0, $mat->get(1, 1));
        $this->assertSame(6.0, $mat->get(1, 2));
    }

    /**
     * Test getting an element with a row index out of range throws OutOfRangeException.
     */
    public function testGetElementRowOutOfRangeThrows(): void
    {
        $mat = new Matrix(2, 2);
        $this->expectException(OutOfRangeException::class);
        $mat->get(2, 0);
    }

    /**
     * Test getting an element with a negative row index throws OutOfRangeException.
     */
    public function testGetElementNegativeRowThrows(): void
    {
        $mat = new Matrix(2, 2);
        $this->expectException(OutOfRangeException::class);
        $mat->get(-1, 0);
    }

    /**
     * Test getting an element with a column index out of range throws OutOfRangeException.
     */
    public function testGetElementColumnOutOfRangeThrows(): void
    {
        $mat = new Matrix(2, 2);
        $this->expectException(OutOfRangeException::class);
        $mat->get(0, 2);
    }

    #endregion

    #region Method getRow() tests.

    /**
     * Test getRow returns the correct Vector.
     */
    public function testGetRowReturnsCorrectVector(): void
    {
        $mat = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $row = $mat->getRow(0);
        $this->assertInstanceOf(Vector::class, $row);
        $this->assertSame([1.0, 2.0, 3.0], $row->toArray());

        $row1 = $mat->getRow(1);
        $this->assertSame([4.0, 5.0, 6.0], $row1->toArray());
    }

    /**
     * Test getRow with out of range index throws OutOfRangeException.
     */
    public function testGetRowOutOfRangeThrows(): void
    {
        $mat = new Matrix(2, 3);
        $this->expectException(OutOfRangeException::class);
        $mat->getRow(2);
    }

    /**
     * Test getRow with negative index throws OutOfRangeException.
     */
    public function testGetRowNegativeThrows(): void
    {
        $mat = new Matrix(2, 3);
        $this->expectException(OutOfRangeException::class);
        $mat->getRow(-1);
    }

    /**
     * Test getRow returns an independent copy: mutating the returned Vector does not affect the Matrix.
     */
    public function testGetRowReturnsIndependentCopy(): void
    {
        $mat = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $row = $mat->getRow(0);
        $row->set(0, 999);

        $this->assertSame(1.0, $mat->get(0, 0));
    }

    #endregion

    #region Method getColumn() tests.

    /**
     * Test getColumn returns the correct Vector.
     */
    public function testGetColumnReturnsCorrectVector(): void
    {
        $mat = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $col = $mat->getColumn(0);
        $this->assertInstanceOf(Vector::class, $col);
        $this->assertSame([1.0, 4.0], $col->toArray());

        $col2 = $mat->getColumn(2);
        $this->assertSame([3.0, 6.0], $col2->toArray());
    }

    /**
     * Test getColumn with out of range index throws OutOfRangeException.
     */
    public function testGetColumnOutOfRangeThrows(): void
    {
        $mat = new Matrix(2, 3);
        $this->expectException(OutOfRangeException::class);
        $mat->getColumn(3);
    }

    /**
     * Test getColumn with negative index throws OutOfRangeException.
     */
    public function testGetColumnNegativeIndexThrows(): void
    {
        $mat = new Matrix(2, 3);
        $this->expectException(OutOfRangeException::class);
        $mat->getColumn(-1);
    }

    #endregion

    #region Method copy() tests.

    /**
     * Test copy extracts the requested sub-matrix.
     */
    public function testCopy(): void
    {
        $mat = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9],
        ]);

        $sub = $mat->copy(1, 1, 2, 2);
        $this->assertSame(2, $sub->rowCount);
        $this->assertSame(2, $sub->columnCount);
        $this->assertSame([
            [5.0, 6.0],
            [8.0, 9.0],
        ], $sub->toArray());
    }

    /**
     * Test copy from the top-left corner (offset 0, 0).
     */
    public function testCopyFromOrigin(): void
    {
        $mat = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);

        $sub = $mat->copy(0, 0, 1, 2);
        $this->assertSame([
            [1.0, 2.0],
        ], $sub->toArray());
    }

    /**
     * Test copy the entire matrix returns an equal but distinct matrix.
     */
    public function testCopyEntireMatrix(): void
    {
        $mat = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);

        $sub = $mat->copy(0, 0, 2, 2);
        $this->assertTrue($mat->equal($sub));
        $this->assertNotSame($mat, $sub);
    }

    /**
     * Test copy with zero rowCount or columnCount returns a degenerate matrix.
     */
    public function testCopyWithZeroDimension(): void
    {
        $mat = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);

        $rows = $mat->copy(0, 0, 0, 2);
        $this->assertSame(0, $rows->rowCount);
        $this->assertSame(2, $rows->columnCount);

        $cols = $mat->copy(0, 0, 2, 0);
        $this->assertSame(2, $cols->rowCount);
        $this->assertSame(0, $cols->columnCount);
    }

    /**
     * Test copy does not mutate the original matrix.
     */
    public function testCopyDoesNotMutateOriginal(): void
    {
        $mat = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);

        $sub = $mat->copy(0, 0, 1, 1);
        $sub->set(0, 0, 99);

        $this->assertSame(1.0, $mat->get(0, 0));
    }

    /**
     * Test copy with a negative row throws OutOfRangeException.
     */
    public function testCopyWithNegativeRowThrows(): void
    {
        $mat = new Matrix(3, 3);
        $this->expectException(OutOfRangeException::class);
        $mat->copy(-1, 0, 1, 1);
    }

    /**
     * Test copy with a negative rowCount throws OutOfRangeException.
     */
    public function testCopyWithNegativeRowCountThrows(): void
    {
        $mat = new Matrix(3, 3);
        $this->expectException(OutOfRangeException::class);
        $mat->copy(0, 0, -1, 1);
    }

    /**
     * Test copy with a row range extending beyond the matrix throws OutOfRangeException.
     */
    public function testCopyWithRowRangeOutOfBoundsThrows(): void
    {
        $mat = new Matrix(3, 3);
        $this->expectException(OutOfRangeException::class);
        $mat->copy(2, 0, 2, 1);
    }

    /**
     * Test copy with a negative column throws OutOfRangeException.
     */
    public function testCopyWithNegativeColumnThrows(): void
    {
        $mat = new Matrix(3, 3);
        $this->expectException(OutOfRangeException::class);
        $mat->copy(0, -1, 1, 1);
    }

    /**
     * Test copy with a negative columnCount throws OutOfRangeException.
     */
    public function testCopyWithNegativeColumnCountThrows(): void
    {
        $mat = new Matrix(3, 3);
        $this->expectException(OutOfRangeException::class);
        $mat->copy(0, 0, 1, -1);
    }

    /**
     * Test copy with a column range extending beyond the matrix throws OutOfRangeException.
     */
    public function testCopyWithColumnRangeOutOfBoundsThrows(): void
    {
        $mat = new Matrix(3, 3);
        $this->expectException(OutOfRangeException::class);
        $mat->copy(0, 2, 1, 2);
    }

    #endregion
}
