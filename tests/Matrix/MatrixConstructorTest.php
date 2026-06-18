<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use DomainException;
use InvalidArgumentException;
use LengthException;
use OceanMoon\Math\Matrix;
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
     * Test fromArray with a non-array row throws InvalidArgumentException.
     */
    public function testFromArrayWithNonArrayRowThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Matrix::fromArray([1, 2, 3]); // @phpstan-ignore argument.type
    }

    /**
     * Test fromArray with non-numeric values throws InvalidArgumentException.
     */
    public function testFromArrayWithNonNumericValuesThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // @phpstan-ignore argument.type
        Matrix::fromArray([
            [1, 'two', 3],
        ]);
    }

    /**
     * Test fromArray with ragged rows throws LengthException.
     */
    public function testFromArrayWithRaggedRowsThrows(): void
    {
        $this->expectException(LengthException::class);
        Matrix::fromArray([
            [1, 2, 3],
            [4, 5],
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
}
