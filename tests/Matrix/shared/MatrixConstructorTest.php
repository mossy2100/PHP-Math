<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use DomainException;
use OceanMoon\Math\Matrix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixConstructorTest extends TestCase
{
    #region Method __construct() tests.

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

    #endregion
}
