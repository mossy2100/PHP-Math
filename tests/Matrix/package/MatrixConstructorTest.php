<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use DomainException;
use OceanMoon\Core\Exceptions\ConversionException;
use OceanMoon\Math\Matrix;
use OceanMoon\Math\Vector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixConstructorTest extends TestCase
{
    /**
     * Test creating a matrix with valid dimensions.
     */
    public function testConstructorWithValidDimensions(): void
    {
        $m = new Matrix(2, 3);
        $this->assertSame(2, $m->rowCount);
        $this->assertSame(3, $m->columnCount);
    }

    /**
     * Test creating a 1x1 matrix.
     */
    public function testConstructorOneByOne(): void
    {
        $m = new Matrix(1, 1);
        $this->assertSame(1, $m->rowCount);
        $this->assertSame(1, $m->columnCount);
    }

    /**
     * Test creating a 0x0 matrix.
     */
    public function testConstructorZeroByZero(): void
    {
        $m = new Matrix(0, 0);
        $this->assertSame(0, $m->rowCount);
        $this->assertSame(0, $m->columnCount);
    }

    /**
     * Test creating a matrix with zero rows.
     */
    public function testConstructorThreeByZero(): void
    {
        $m = new Matrix(3, 0);
        $this->assertSame(3, $m->rowCount);
        $this->assertSame(0, $m->columnCount);
    }

    /**
     * Test creating a matrix with zero columns.
     */
    public function testConstructorZeroByThree(): void
    {
        $m = new Matrix(0, 3);
        $this->assertSame(0, $m->rowCount);
        $this->assertSame(3, $m->columnCount);
    }

    /**
     * Test that negative row count throws DomainException.
     */
    public function testConstructorNegativeRowCountThrows(): void
    {
        $this->expectException(DomainException::class);
        new Matrix(-1, 3);
    }

    /**
     * Test that negative column count throws DomainException.
     */
    public function testConstructorNegativeColumnCountThrows(): void
    {
        $this->expectException(DomainException::class);
        new Matrix(3, -1);
    }

    /**
     * Test that both negative dimensions throws DomainException.
     */
    public function testConstructorBothNegativeThrows(): void
    {
        $this->expectException(DomainException::class);
        new Matrix(-2, -3);
    }

    /**
     * Test that the constructor initialises all elements to zero.
     */
    public function testConstructorInitialisesElementsToZero(): void
    {
        $m = new Matrix(2, 3);
        for ($i = 0; $i < 2; $i++) {
            for ($j = 0; $j < 3; $j++) {
                $this->assertSame(0.0, $m->get($i, $j));
            }
        }
    }

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

    /**
     * Test fromArray with a valid 2D array.
     */
    public function testFromArrayWithValidData(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $this->assertSame(2, $m->rowCount);
        $this->assertSame(3, $m->columnCount);
        $this->assertSame(1.0, $m->get(0, 0));
        $this->assertSame(6.0, $m->get(1, 2));
    }

    /**
     * Test fromArray with float values.
     */
    public function testFromArrayWithFloats(): void
    {
        $m = Matrix::fromArray([
            [1.5, 2.5],
            [3.5, 4.5],
        ]);
        $this->assertSame(1.5, $m->get(0, 0));
        $this->assertSame(4.5, $m->get(1, 1));
    }

    /**
     * Test fromArray with an empty array creates a 0x0 matrix.
     */
    public function testFromArrayWithEmptyArray(): void
    {
        $m = Matrix::fromArray([]);
        $this->assertSame(0, $m->rowCount);
        $this->assertSame(0, $m->columnCount);
    }

    /**
     * Test fromArray with a non-array row throws ConversionException.
     */
    public function testFromArrayWithNonArrayRowThrows(): void
    {
        $this->expectException(ConversionException::class);
        Matrix::fromArray([1, 2, 3]);
    }

    /**
     * Test fromArray with non-numeric values throws ConversionException.
     */
    public function testFromArrayWithNonNumericValuesThrows(): void
    {
        $this->expectException(ConversionException::class);
        Matrix::fromArray([
            [1, 'two', 3],
        ]);
    }

    /**
     * Test fromArray with ragged rows throws ConversionException.
     */
    public function testFromArrayWithRaggedRowsThrows(): void
    {
        $this->expectException(ConversionException::class);
        Matrix::fromArray([
            [1, 2, 3],
            [4, 5],
        ]);
    }

    /**
     * Test fromArray with the outer array not a list (non-sequential keys) throws ConversionException.
     */
    public function testFromArrayWithNonListOuterArrayThrows(): void
    {
        $this->expectException(ConversionException::class);
        Matrix::fromArray([
            5 => [1, 2],
            9 => [3, 4],
        ]);
    }

    /**
     * Test fromArray with a row that is not a list (non-sequential keys) throws ConversionException.
     */
    public function testFromArrayWithNonListRowThrows(): void
    {
        $this->expectException(ConversionException::class);
        Matrix::fromArray([
            [1, 2],
            [
                5 => 3,
                9 => 4,
            ],
        ]);
    }

    /**
     * Test identity matrix 1x1.
     */
    public function testIdentityOneByOne(): void
    {
        $m = Matrix::identity(1);
        $this->assertSame(1, $m->rowCount);
        $this->assertSame(1, $m->columnCount);
        $this->assertSame(1.0, $m->get(0, 0));
    }

    /**
     * Test identity matrix 3x3.
     */
    public function testIdentityThreeByThree(): void
    {
        $m = Matrix::identity(3);
        $this->assertSame(3, $m->rowCount);
        $this->assertSame(3, $m->columnCount);

        // Verify diagonal is 1 and off-diagonal is 0.
        for ($i = 0; $i < 3; $i++) {
            for ($j = 0; $j < 3; $j++) {
                if ($i === $j) {
                    $this->assertSame(1.0, $m->get($i, $j));
                } else {
                    $this->assertSame(0.0, $m->get($i, $j));
                }
            }
        }
    }

    /**
     * Test toMatrix with a Matrix instance returns it unchanged (same instance).
     */
    public function testToMatrixWithMatrixInstance(): void
    {
        $m = new Matrix(2, 2);
        $result = Matrix::toMatrix($m);
        $this->assertSame($m, $result);
    }

    /**
     * Test toMatrix with a Vector converts it to a single-column matrix.
     */
    public function testToMatrixWithVector(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $m = Matrix::toMatrix($v);

        $this->assertSame(3, $m->rowCount);
        $this->assertSame(1, $m->columnCount);
        $this->assertSame([
            [1.0],
            [2.0],
            [3.0],
        ], $m->toArray());
    }

    /**
     * Test toMatrix with a flat list of numbers converts it to a single-column matrix, matching how
     * a bare Vector is treated.
     */
    public function testToMatrixWithFlatArray(): void
    {
        $m = Matrix::toMatrix([1, 2, 3]);

        $this->assertSame(3, $m->rowCount);
        $this->assertSame(1, $m->columnCount);
        $this->assertSame([
            [1.0],
            [2.0],
            [3.0],
        ], $m->toArray());
    }

    /**
     * Test toMatrix with a non-list array of numbers throws ConversionException. This exercises
     * isFlatNumericArray()'s array_is_list() check specifically: the array contains only numbers, but
     * its keys are non-sequential, so it fails the flat-numeric-array check and falls through to
     * fromArray(), which then also rejects it for not being a list.
     */
    public function testToMatrixWithNonListNumericArrayThrows(): void
    {
        $this->expectException(ConversionException::class);
        Matrix::toMatrix([
            5 => 1,
            9 => 2,
            3 => 3,
        ]);
    }

    /**
     * Test toMatrix with a rectangular array of rows delegates to fromArray().
     */
    public function testToMatrixWithRectangularArray(): void
    {
        $m = Matrix::toMatrix([
            [1, 2],
            [3, 4],
        ]);

        $this->assertSame(2, $m->rowCount);
        $this->assertSame(2, $m->columnCount);
        $this->assertSame([
            [1.0, 2.0],
            [3.0, 4.0],
        ], $m->toArray());
    }

    /**
     * Test toMatrix with a ragged array throws ConversionException, rethrown from fromArray().
     */
    public function testToMatrixWithRaggedArrayThrows(): void
    {
        $this->expectException(ConversionException::class);
        Matrix::toMatrix([
            [1, 2],
            [3],
        ]);
    }

    /**
     * Test toMatrix with a value of an unconvertible type throws ConversionException.
     */
    public function testToMatrixWithInvalidTypeThrows(): void
    {
        $this->expectException(ConversionException::class);
        Matrix::toMatrix(null);
    }

    /**
     * Test toMatrix with a string (not a valid conversion source) throws ConversionException.
     */
    public function testToMatrixWithStringThrows(): void
    {
        $this->expectException(ConversionException::class);
        Matrix::toMatrix('not a matrix');
    }
}
