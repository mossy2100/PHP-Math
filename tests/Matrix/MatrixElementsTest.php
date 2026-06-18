<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use InvalidArgumentException;
use LengthException;
use OceanMoon\Math\Matrix;
use OceanMoon\Math\Vector;
use OutOfRangeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixElementsTest extends TestCase
{
    // region get() tests

    /**
     * Test getting a valid element.
     */
    public function testGetValidElement(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $this->assertSame(1.0, $m->get(0, 0));
        $this->assertSame(5.0, $m->get(1, 1));
        $this->assertSame(6.0, $m->get(1, 2));
    }

    /**
     * Test getting an element with a row index out of range throws OutOfRangeException.
     */
    public function testGetElementRowOutOfRangeThrows(): void
    {
        $m = new Matrix(2, 2);
        $this->expectException(OutOfRangeException::class);
        $m->get(2, 0);
    }

    /**
     * Test getting an element with a negative row index throws OutOfRangeException.
     */
    public function testGetElementNegativeRowThrows(): void
    {
        $m = new Matrix(2, 2);
        $this->expectException(OutOfRangeException::class);
        $m->get(-1, 0);
    }

    /**
     * Test getting an element with a column index out of range throws OutOfRangeException.
     */
    public function testGetElementColumnOutOfRangeThrows(): void
    {
        $m = new Matrix(2, 2);
        $this->expectException(OutOfRangeException::class);
        $m->get(0, 2);
    }

    // endregion

    // region set() tests

    /**
     * Test setting a valid element.
     */
    public function testSetValidElement(): void
    {
        $m = new Matrix(2, 2);
        $m->set(0, 1, 42);
        $this->assertSame(42.0, $m->get(0, 1));
    }

    /**
     * Test setting a float element.
     */
    public function testSetFloatElement(): void
    {
        $m = new Matrix(2, 2);
        $m->set(1, 0, 3.14);
        $this->assertSame(3.14, $m->get(1, 0));
    }

    /**
     * Test setting an element out of range throws OutOfRangeException.
     */
    public function testSetOutOfRangeThrows(): void
    {
        $m = new Matrix(2, 2);
        $this->expectException(OutOfRangeException::class);
        $m->set(2, 0, 1);
    }

    /**
     * Test setting an element with a negative index throws OutOfRangeException.
     */
    public function testSetNegativeIndexThrows(): void
    {
        $m = new Matrix(2, 2);
        $this->expectException(OutOfRangeException::class);
        $m->set(0, -1, 1);
    }

    // endregion

    // region getRow() tests

    /**
     * Test getRow returns the correct Vector.
     */
    public function testGetRowReturnsCorrectVector(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $row = $m->getRow(0);
        $this->assertInstanceOf(Vector::class, $row);
        $this->assertSame([1.0, 2.0, 3.0], $row->toArray());

        $row1 = $m->getRow(1);
        $this->assertSame([4.0, 5.0, 6.0], $row1->toArray());
    }

    /**
     * Test getRow with out of range index throws OutOfRangeException.
     */
    public function testGetRowOutOfRangeThrows(): void
    {
        $m = new Matrix(2, 3);
        $this->expectException(OutOfRangeException::class);
        $m->getRow(2);
    }

    /**
     * Test getRow with negative index throws OutOfRangeException.
     */
    public function testGetRowNegativeThrows(): void
    {
        $m = new Matrix(2, 3);
        $this->expectException(OutOfRangeException::class);
        $m->getRow(-1);
    }

    // endregion

    // region setRow() tests

    /**
     * Test setRow with an array.
     */
    public function testSetRowWithArray(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $m->setRow(0, [7, 8, 9]);

        $this->assertSame([7.0, 8.0, 9.0], $m->getRow(0)->toArray());
        $this->assertSame([4.0, 5.0, 6.0], $m->getRow(1)->toArray());
    }

    /**
     * Test setRow with a Vector.
     */
    public function testSetRowWithVector(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $m->setRow(1, Vector::fromArray([10, 11, 12]));

        $this->assertSame([1.0, 2.0, 3.0], $m->getRow(0)->toArray());
        $this->assertSame([10.0, 11.0, 12.0], $m->getRow(1)->toArray());
    }

    /**
     * Test setRow with out of range index throws OutOfRangeException.
     */
    public function testSetRowOutOfRangeThrows(): void
    {
        $m = new Matrix(2, 3);
        $this->expectException(OutOfRangeException::class);
        $m->setRow(2, [1, 2, 3]);
    }

    /**
     * Test setRow with wrong length throws LengthException.
     */
    public function testSetRowWrongLengthThrows(): void
    {
        $m = new Matrix(2, 3);
        $this->expectException(LengthException::class);
        $m->setRow(0, [1, 2]);
    }

    /**
     * Test setRow with non-numeric elements throws InvalidArgumentException.
     */
    public function testSetRowNonNumericThrows(): void
    {
        $m = new Matrix(2, 3);
        $this->expectException(InvalidArgumentException::class);
        $m->setRow(0, [1, 'two', 3]); // @phpstan-ignore argument.type
    }

    // endregion

    // region getColumn() tests

    /**
     * Test getColumn returns the correct Vector.
     */
    public function testGetColumnReturnsCorrectVector(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $col = $m->getColumn(0);
        $this->assertInstanceOf(Vector::class, $col);
        $this->assertSame([1.0, 4.0], $col->toArray());

        $col2 = $m->getColumn(2);
        $this->assertSame([3.0, 6.0], $col2->toArray());
    }

    /**
     * Test getColumn with out of range index throws OutOfRangeException.
     */
    public function testGetColumnOutOfRangeThrows(): void
    {
        $m = new Matrix(2, 3);
        $this->expectException(OutOfRangeException::class);
        $m->getColumn(3);
    }

    /**
     * Test getColumn with negative index throws OutOfRangeException.
     */
    public function testGetColumnNegativeIndexThrows(): void
    {
        $m = new Matrix(2, 3);
        $this->expectException(OutOfRangeException::class);
        $m->getColumn(-1);
    }

    // endregion

    // region setColumn() tests

    /**
     * Test setColumn with an array.
     */
    public function testSetColumnWithArray(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $m->setColumn(1, [20, 50]);

        $this->assertSame([1.0, 20.0, 3.0], $m->getRow(0)->toArray());
        $this->assertSame([4.0, 50.0, 6.0], $m->getRow(1)->toArray());
    }

    /**
     * Test setColumn with a Vector.
     */
    public function testSetColumnWithVector(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $m->setColumn(2, Vector::fromArray([30, 60]));

        $this->assertSame([1.0, 2.0, 30.0], $m->getRow(0)->toArray());
        $this->assertSame([4.0, 5.0, 60.0], $m->getRow(1)->toArray());
    }

    /**
     * Test setColumn with out of range index throws OutOfRangeException.
     */
    public function testSetColumnOutOfRangeThrows(): void
    {
        $m = new Matrix(2, 3);
        $this->expectException(OutOfRangeException::class);
        $m->setColumn(3, [1, 2]);
    }

    /**
     * Test setColumn with wrong length throws LengthException.
     */
    public function testSetColumnWrongLengthThrows(): void
    {
        $m = new Matrix(2, 3);
        $this->expectException(LengthException::class);
        $m->setColumn(0, [1, 2, 3]);
    }

    /**
     * Test setColumn with non-numeric elements throws InvalidArgumentException.
     */
    public function testSetColumnNonNumericThrows(): void
    {
        $m = new Matrix(2, 3);
        $this->expectException(InvalidArgumentException::class);
        $m->setColumn(0, [1, 'two']); // @phpstan-ignore argument.type
    }

    // endregion

    // region isSquare() tests

    /**
     * Test isSquare with a square matrix.
     */
    public function testIsSquareWithSquareMatrix(): void
    {
        $m = new Matrix(3, 3);
        $this->assertTrue($m->isSquare());
    }

    /**
     * Test isSquare with a non-square matrix.
     */
    public function testIsSquareWithNonSquareMatrix(): void
    {
        $m = new Matrix(2, 3);
        $this->assertFalse($m->isSquare());
    }

    /**
     * Test isSquare with a specific size that matches.
     */
    public function testIsSquareWithSpecificSizeMatches(): void
    {
        $m = new Matrix(3, 3);
        $this->assertTrue($m->isSquare(3));
    }

    /**
     * Test isSquare with a specific size that does not match.
     */
    public function testIsSquareWithSpecificSizeDoesNotMatch(): void
    {
        $m = new Matrix(3, 3);
        $this->assertFalse($m->isSquare(2));
    }

    /**
     * Test isSquare with specific size on a non-square matrix.
     */
    public function testIsSquareWithSpecificSizeOnNonSquareMatrix(): void
    {
        $m = new Matrix(2, 3);
        $this->assertFalse($m->isSquare(2));
    }

    // endregion
}
