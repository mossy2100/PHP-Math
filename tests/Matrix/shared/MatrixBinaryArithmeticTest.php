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
        $m1 = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $m2 = Matrix::fromArray([
            [5, 6],
            [7, 8],
        ]);
        $result = $m1->add($m2);
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
        $m1 = new Matrix(2, 2);
        $m2 = new Matrix(2, 3);
        $this->expectException(LengthException::class);
        $m1->add($m2);
    }

    #endregion

    #region Method sub() tests.

    /**
     * Test subtracting two matrices.
     */
    public function testSub(): void
    {
        $m1 = Matrix::fromArray([
            [5, 6],
            [7, 8],
        ]);
        $m2 = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $result = $m1->sub($m2);
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
        $m1 = new Matrix(2, 3);
        $m2 = new Matrix(3, 2);
        $this->expectException(LengthException::class);
        $m1->sub($m2);
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
        $m1 = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $m2 = Matrix::fromArray([
            [7, 8],
            [9, 10],
            [11, 12],
        ]);
        $result = $m1->mul($m2);
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
        $m1 = new Matrix(2, 3);
        $m2 = new Matrix(2, 2);
        $this->expectException(LengthException::class);
        $m1->mul($m2);
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

    #endregion

    #region Method hadamardMul() tests.

    /**
     * Test the Hadamard (element-wise) product of two matrices.
     */
    public function testHadamardMul(): void
    {
        $m1 = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $m2 = Matrix::fromArray([
            [5, 6],
            [7, 8],
        ]);
        $result = $m1->hadamardMul($m2);
        $this->assertEqualsWithDelta(5.0, $result->get(0, 0), EPSILON);
        $this->assertEqualsWithDelta(12.0, $result->get(0, 1), EPSILON);
        $this->assertEqualsWithDelta(21.0, $result->get(1, 0), EPSILON);
        $this->assertEqualsWithDelta(32.0, $result->get(1, 1), EPSILON);
    }

    /**
     * Test the Hadamard product of matrices with different dimensions throws LengthException.
     */
    public function testHadamardMulWithDifferentDimensionsThrows(): void
    {
        $m1 = new Matrix(2, 2);
        $m2 = new Matrix(3, 3);
        $this->expectException(LengthException::class);
        $m1->hadamardMul($m2);
    }

    #endregion

    #region Method hadamardDiv() tests.

    /**
     * Test the Hadamard (element-wise) division of two matrices.
     */
    public function testHadamardDiv(): void
    {
        $m1 = Matrix::fromArray([
            [5, 12],
            [21, 32],
        ]);
        $m2 = Matrix::fromArray([
            [5, 6],
            [7, 8],
        ]);
        $result = $m1->hadamardDiv($m2);
        $this->assertEqualsWithDelta(1.0, $result->get(0, 0), EPSILON);
        $this->assertEqualsWithDelta(2.0, $result->get(0, 1), EPSILON);
        $this->assertEqualsWithDelta(3.0, $result->get(1, 0), EPSILON);
        $this->assertEqualsWithDelta(4.0, $result->get(1, 1), EPSILON);
    }

    /**
     * Test the Hadamard division of matrices with different dimensions throws LengthException.
     */
    public function testHadamardDivWithDifferentDimensionsThrows(): void
    {
        $m1 = new Matrix(2, 2);
        $m2 = new Matrix(3, 3);
        $this->expectException(LengthException::class);
        $m1->hadamardDiv($m2);
    }

    /**
     * Test the Hadamard division by a matrix containing a zero element throws ArithmeticException.
     */
    public function testHadamardDivByZeroElementThrows(): void
    {
        $m1 = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $m2 = Matrix::fromArray([
            [1, 0],
            [1, 1],
        ]);
        $this->expectException(ArithmeticException::class);
        $m1->hadamardDiv($m2);
    }

    #endregion
}
