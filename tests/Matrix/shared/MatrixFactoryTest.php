<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use DomainException;
use LengthException;
use OceanMoon\Math\Matrix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixFactoryTest extends TestCase
{
    #region Method fromArray() tests.

    /**
     * Test fromArray with a valid 2D array.
     */
    public function testFromArrayWithValidData(): void
    {
        $mat = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $this->assertSame(2, $mat->rowCount);
        $this->assertSame(3, $mat->columnCount);
        $this->assertSame(1.0, $mat->get(0, 0));
        $this->assertSame(6.0, $mat->get(1, 2));
    }

    /**
     * Test fromArray with float values.
     */
    public function testFromArrayWithFloats(): void
    {
        $mat = Matrix::fromArray([
            [1.5, 2.5],
            [3.5, 4.5],
        ]);
        $this->assertSame(1.5, $mat->get(0, 0));
        $this->assertSame(4.5, $mat->get(1, 1));
    }

    /**
     * Test fromArray with an empty array creates a 0x0 matrix.
     */
    public function testFromArrayWithEmptyArray(): void
    {
        $mat = Matrix::fromArray([]);
        $this->assertSame(0, $mat->rowCount);
        $this->assertSame(0, $mat->columnCount);
    }

    /**
     * Test fromArray with a non-array row throws DomainException.
     */
    public function testFromArrayWithNonArrayRowThrows(): void
    {
        $this->expectException(DomainException::class);
        Matrix::fromArray([1, 2, 3]);
    }

    /**
     * Test fromArray with non-numeric values throws DomainException.
     */
    public function testFromArrayWithNonNumericValuesThrows(): void
    {
        $this->expectException(DomainException::class);
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
     * Test fromArray with the outer array not a list (non-sequential keys) throws DomainException.
     */
    public function testFromArrayWithNonListOuterArrayThrows(): void
    {
        $this->expectException(DomainException::class);
        Matrix::fromArray([
            5 => [1, 2],
            9 => [3, 4],
        ]);
    }

    /**
     * Test fromArray with a row that is not a list (non-sequential keys) throws DomainException.
     */
    public function testFromArrayWithNonListRowThrows(): void
    {
        $this->expectException(DomainException::class);
        Matrix::fromArray([
            [1, 2],
            [
                5 => 3,
                9 => 4,
            ],
        ]);
    }

    #endregion

    #region Method identity() tests.

    /**
     * Test identity matrix 1x1.
     */
    public function testIdentityOneByOne(): void
    {
        $matI = Matrix::identity(1);
        $this->assertSame(1, $matI->rowCount);
        $this->assertSame(1, $matI->columnCount);
        $this->assertSame(1.0, $matI->get(0, 0));
    }

    /**
     * Test identity matrix 3x3.
     */
    public function testIdentityThreeByThree(): void
    {
        $matI = Matrix::identity(3);
        $this->assertSame(3, $matI->rowCount);
        $this->assertSame(3, $matI->columnCount);

        // Verify diagonal is 1 and off-diagonal is 0.
        for ($i = 0; $i < 3; $i++) {
            for ($j = 0; $j < 3; $j++) {
                if ($i === $j) {
                    $this->assertSame(1.0, $matI->get($i, $j));
                } else {
                    $this->assertSame(0.0, $matI->get($i, $j));
                }
            }
        }
    }

    #endregion
}
