<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use LengthException;
use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Math\Matrix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixBinaryArithmeticTest extends TestCase
{
    #region Method add() tests.

    /**
     * Test adding two matrices.
     */
    public function testAdd(): void
    {
        $a = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $b = Matrix::fromArray([
            [5, 6],
            [7, 8],
        ]);
        $result = $a->add($b);
        $this->assertSame([
            [6.0, 8.0],
            [10.0, 12.0],
        ], $result->toArray());
    }

    /**
     * Test adding matrices with different dimensions throws LengthException.
     */
    public function testAddDifferentDimensionsThrows(): void
    {
        $a = new Matrix(2, 2);
        $b = new Matrix(2, 3);
        $this->expectException(LengthException::class);
        $a->add($b);
    }

    #endregion

    #region Method sub() tests.

    /**
     * Test subtracting two matrices.
     */
    public function testSub(): void
    {
        $a = Matrix::fromArray([
            [5, 6],
            [7, 8],
        ]);
        $b = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $result = $a->sub($b);
        $this->assertSame([
            [4.0, 4.0],
            [4.0, 4.0],
        ], $result->toArray());
    }

    /**
     * Test subtracting matrices with different dimensions throws LengthException.
     */
    public function testSubDifferentDimensionsThrows(): void
    {
        $a = new Matrix(2, 3);
        $b = new Matrix(3, 2);
        $this->expectException(LengthException::class);
        $a->sub($b);
    }

    #endregion

    #region Method mul() tests.

    /**
     * Test multiplying a matrix by an integer scalar.
     */
    public function testMulByIntScalar(): void
    {
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $result = $m->mul(3);
        $this->assertSame([
            [3.0, 6.0],
            [9.0, 12.0],
        ], $result->toArray());
    }

    /**
     * Test multiplying a matrix by a float scalar.
     */
    public function testMulByFloatScalar(): void
    {
        $m = Matrix::fromArray([
            [2, 4],
            [6, 8],
        ]);
        $result = $m->mul(0.5);
        $this->assertSame([
            [1.0, 2.0],
            [3.0, 4.0],
        ], $result->toArray());
    }

    /**
     * Test multiplying two matrices with known result.
     */
    public function testMulByMatrix(): void
    {
        // 2x3 * 3x2 = 2x2
        $a = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $b = Matrix::fromArray([
            [7, 8],
            [9, 10],
            [11, 12],
        ]);
        $result = $a->mul($b);
        $this->assertInstanceOf(Matrix::class, $result);
        $this->assertSame(2, $result->rowCount);
        $this->assertSame(2, $result->columnCount);
        // Row 0: 1*7+2*9+3*11=58, 1*8+2*10+3*12=64
        // Row 1: 4*7+5*9+6*11=139, 4*8+5*10+6*12=154
        $this->assertEqualsWithDelta(58.0, $result->get(0, 0), EPSILON);
        $this->assertEqualsWithDelta(64.0, $result->get(0, 1), EPSILON);
        $this->assertEqualsWithDelta(139.0, $result->get(1, 0), EPSILON);
        $this->assertEqualsWithDelta(154.0, $result->get(1, 1), EPSILON);
    }

    /**
     * Test multiplying matrices with incompatible dimensions throws LengthException.
     */
    public function testMulByMatrixIncompatibleDimensionsThrows(): void
    {
        $a = new Matrix(2, 3);
        $b = new Matrix(2, 2);
        $this->expectException(LengthException::class);
        $a->mul($b);
    }

    #endregion

    #region Method div() tests.

    /**
     * Test dividing a matrix by a scalar.
     */
    public function testDivByScalar(): void
    {
        $m = Matrix::fromArray([
            [4, 8],
            [12, 16],
        ]);
        $result = $m->div(4);
        $this->assertEqualsWithDelta(1.0, $result->get(0, 0), EPSILON);
        $this->assertEqualsWithDelta(2.0, $result->get(0, 1), EPSILON);
        $this->assertEqualsWithDelta(3.0, $result->get(1, 0), EPSILON);
        $this->assertEqualsWithDelta(4.0, $result->get(1, 1), EPSILON);
    }

    /**
     * Test dividing a matrix by zero throws ArithmeticException.
     */
    public function testDivByZeroThrows(): void
    {
        $m = new Matrix(2, 2);
        $this->expectException(ArithmeticException::class);
        $m->div(0);
    }

    /**
     * Test dividing a matrix by another matrix (A * B^-1).
     */
    public function testDivByMatrix(): void
    {
        $a = Matrix::fromArray([
            [1, 0],
            [0, 1],
        ]);
        $b = Matrix::fromArray([
            [2, 0],
            [0, 2],
        ]);
        $result = $a->div($b);
        // I * (2I)^-1 = 0.5I
        $this->assertEqualsWithDelta(0.5, $result->get(0, 0), EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->get(0, 1), EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->get(1, 0), EPSILON);
        $this->assertEqualsWithDelta(0.5, $result->get(1, 1), EPSILON);
    }

    #endregion

    #region Method hadamard() tests.

    /**
     * Test the Hadamard (element-wise) product of two matrices.
     */
    public function testHadamard(): void
    {
        $a = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $b = Matrix::fromArray([
            [5, 6],
            [7, 8],
        ]);
        $result = $a->hadamard($b);
        $this->assertEqualsWithDelta(5.0, $result->get(0, 0), EPSILON);
        $this->assertEqualsWithDelta(12.0, $result->get(0, 1), EPSILON);
        $this->assertEqualsWithDelta(21.0, $result->get(1, 0), EPSILON);
        $this->assertEqualsWithDelta(32.0, $result->get(1, 1), EPSILON);
    }

    /**
     * Test the Hadamard product of matrices with different dimensions throws LengthException.
     */
    public function testHadamardWithDifferentDimensionsThrows(): void
    {
        $a = new Matrix(2, 2);
        $b = new Matrix(3, 3);
        $this->expectException(LengthException::class);
        $a->hadamard($b);
    }

    #endregion
}
