<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Vector;

use LengthException;
use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Math\Matrix;
use OceanMoon\Math\Vector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Vector::class)]
class VectorBinaryArithmeticTest extends TestCase
{
    #region Method add() tests.

    /**
     * Test adding two vectors.
     */
    public function testAdd(): void
    {
        $a = Vector::fromArray([1, 2, 3]);
        $b = Vector::fromArray([4, 5, 6]);
        $result = $a->add($b);
        $this->assertSame([5.0, 7.0, 9.0], $result->toArray());
    }

    /**
     * Test adding vectors with different sizes throws LengthException.
     */
    public function testAddWithDifferentSizesThrows(): void
    {
        $a = Vector::fromArray([1, 2]);
        $b = Vector::fromArray([1, 2, 3]);
        $this->expectException(LengthException::class);
        $a->add($b);
    }

    #endregion

    #region Method sub() tests.

    /**
     * Test subtracting two vectors.
     */
    public function testSub(): void
    {
        $a = Vector::fromArray([10, 20, 30]);
        $b = Vector::fromArray([1, 2, 3]);
        $result = $a->sub($b);
        $this->assertSame([9.0, 18.0, 27.0], $result->toArray());
    }

    /**
     * Test subtracting vectors with different sizes throws LengthException.
     */
    public function testSubWithDifferentSizesThrows(): void
    {
        $a = Vector::fromArray([1, 2, 3]);
        $b = Vector::fromArray([1, 2]);
        $this->expectException(LengthException::class);
        $a->sub($b);
    }

    #endregion

    #region Method mul() tests.

    /**
     * Test multiplying a vector by an integer scalar.
     */
    public function testMulByInt(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $result = $v->mul(3);
        $this->assertSame([3.0, 6.0, 9.0], $result->toArray());
    }

    /**
     * Test multiplying a vector by a float scalar.
     */
    public function testMulByFloat(): void
    {
        $v = Vector::fromArray([2, 4, 6]);
        $result = $v->mul(0.5);
        $this->assertSame([1.0, 2.0, 3.0], $result->toArray());
    }

    /**
     * Test multiplying a vector by zero.
     */
    public function testMulByZero(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $result = $v->mul(0);
        $this->assertSame([0.0, 0.0, 0.0], $result->toArray());
    }

    /**
     * Test multiplying a vector by a negative scalar.
     */
    public function testMulByNegative(): void
    {
        $v = Vector::fromArray([1, -2, 3]);
        $result = $v->mul(-2);
        $this->assertSame([-2.0, 4.0, -6.0], $result->toArray());
    }

    /**
     * Test multiplying a vector by a Matrix. The vector is treated as a single-row matrix, multiplied
     * by the given Matrix, and the resulting single row is converted back to a Vector.
     */
    public function testMulByMatrix(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $m = Matrix::fromArray([
            [1, 4],
            [2, 5],
            [3, 6],
        ]);
        $result = $v->mul($m);

        $this->assertInstanceOf(Vector::class, $result);
        // [1,2,3] * M = [1*1+2*2+3*3, 1*4+2*5+3*6] = [14, 32]
        $this->assertSame([14.0, 32.0], $result->toArray());
    }

    /**
     * Test multiplying a vector by a Matrix with an incompatible row count throws LengthException.
     */
    public function testMulByMatrixIncompatibleDimensionsThrows(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $m = new Matrix(2, 2);

        $this->expectException(LengthException::class);
        $v->mul($m);
    }

    #endregion

    #region Method div() tests.

    /**
     * Test dividing a vector by a scalar.
     */
    public function testDiv(): void
    {
        $v = Vector::fromArray([10, 20, 30]);
        $result = $v->div(10);
        $this->assertEqualsWithDelta([1.0, 2.0, 3.0], $result->toArray(), EPSILON);
    }

    /**
     * Test dividing a vector by zero throws ArithmeticException.
     */
    public function testDivByZeroThrows(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $this->expectException(ArithmeticException::class);
        $v->div(0);
    }

    #endregion

    #region Method hadamardMul() tests.

    /**
     * Test the Hadamard (element-wise) product of two vectors.
     */
    public function testHadamardMul(): void
    {
        $a = Vector::fromArray([1, 2, 3]);
        $b = Vector::fromArray([4, 5, 6]);
        $result = $a->hadamardMul($b);
        $this->assertEqualsWithDelta([4.0, 10.0, 18.0], $result->toArray(), EPSILON);
    }

    /**
     * Test the Hadamard product of vectors with different sizes throws LengthException.
     */
    public function testHadamardMulWithDifferentSizesThrows(): void
    {
        $a = Vector::fromArray([1, 2, 3]);
        $b = Vector::fromArray([1, 2]);
        $this->expectException(LengthException::class);
        $a->hadamardMul($b);
    }

    #endregion

    #region Method hadamardDiv() tests.

    /**
     * Test the Hadamard (element-wise) division of two vectors.
     */
    public function testHadamardDiv(): void
    {
        $a = Vector::fromArray([4, 10, 18]);
        $b = Vector::fromArray([4, 5, 6]);
        $result = $a->hadamardDiv($b);
        $this->assertEqualsWithDelta([1.0, 2.0, 3.0], $result->toArray(), EPSILON);
    }

    /**
     * Test the Hadamard division of vectors with different sizes throws LengthException.
     */
    public function testHadamardDivWithDifferentSizesThrows(): void
    {
        $a = Vector::fromArray([1, 2, 3]);
        $b = Vector::fromArray([1, 2]);
        $this->expectException(LengthException::class);
        $a->hadamardDiv($b);
    }

    /**
     * Test the Hadamard division by a vector containing a zero element throws ArithmeticException.
     */
    public function testHadamardDivByZeroElementThrows(): void
    {
        $a = Vector::fromArray([1, 2, 3]);
        $b = Vector::fromArray([1, 0, 1]);
        $this->expectException(ArithmeticException::class);
        $a->hadamardDiv($b);
    }

    #endregion
}
