<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use DivisionByZeroError;
use DomainException;
use LengthException;
use OceanMoon\Math\Matrix;
use OceanMoon\Math\Vector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixArithmeticTest extends TestCase
{
    /**
     * Test negating a matrix.
     */
    public function testNeg(): void
    {
        $m = Matrix::fromArray([
            [1, -2],
            [3, -4],
        ]);
        $result = $m->neg();
        $this->assertSame([
            [-1.0, 2.0],
            [-3.0, 4.0],
        ], $result->toArray());
    }

    /**
     * Test negating a zero matrix returns a zero matrix.
     */
    public function testNegZeroMatrix(): void
    {
        $m = new Matrix(2, 2);
        $result = $m->neg();
        $this->assertSame([
            [0.0, 0.0],
            [0.0, 0.0],
        ], $result->toArray());
    }

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

    /**
     * Test multiplying a matrix by a Vector (treated as a column vector).
     */
    public function testMulByVector(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $v = Vector::fromArray([1, 2, 3]);
        $result = $m->mul($v);
        $this->assertInstanceOf(Vector::class, $result);
        // Row 0: 1*1+2*2+3*3=14
        // Row 1: 4*1+5*2+6*3=32
        $this->assertEqualsWithDelta(14.0, $result->toArray()[0], EPSILON);
        $this->assertEqualsWithDelta(32.0, $result->toArray()[1], EPSILON);
    }

    /**
     * Test multiplying a 0-row matrix by a Vector returns a size-0 Vector.
     */
    public function testMulByVectorWithZeroRowMatrix(): void
    {
        $m = new Matrix(0, 3);
        $v = Vector::fromArray([1, 2, 3]);
        $result = $m->mul($v);
        $this->assertInstanceOf(Vector::class, $result);
        $this->assertSame(0, $result->size);
    }

    /**
     * Test multiplying a 0-column matrix by an empty Vector returns a zero vector matching the
     * matrix's row count. This exercises Vector::toColumnMatrix()'s handling of an empty vector,
     * which must produce a genuine n×1 (here 0×1) matrix rather than a 0×0 one, or the inner matrix
     * multiplication silently produces the wrong shape.
     */
    public function testMulByEmptyVectorWithZeroColumnMatrix(): void
    {
        $m = new Matrix(3, 0);
        $v = new Vector(0);
        $result = $m->mul($v);
        $this->assertInstanceOf(Vector::class, $result);
        $this->assertSame(3, $result->size);
        $this->assertSame([0.0, 0.0, 0.0], $result->toArray());
    }

    /**
     * Test multiplying a matrix with a non-zero column count by an empty Vector throws
     * LengthException, since the dimensions are genuinely incompatible.
     */
    public function testMulByEmptyVectorWithIncompatibleMatrixThrows(): void
    {
        $m = new Matrix(3, 2);
        $this->expectException(LengthException::class);
        $m->mul(new Vector(0));
    }

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
     * Test dividing a matrix by zero throws DivisionByZeroError.
     */
    public function testDivByZeroThrows(): void
    {
        $m = new Matrix(2, 2);
        $this->expectException(DivisionByZeroError::class);
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

    /**
     * Test inverse of a 2x2 matrix.
     */
    public function testInvTwoByTwo(): void
    {
        $a = Matrix::fromArray([
            [4, 7],
            [2, 6],
        ]);
        $inv = $a->inv();

        // Verify A * A^-1 = I.
        $product = $a->mul($inv);
        $this->assertEqualsWithDelta(1.0, $product->get(0, 0), EPSILON);
        $this->assertEqualsWithDelta(0.0, $product->get(0, 1), EPSILON);
        $this->assertEqualsWithDelta(0.0, $product->get(1, 0), EPSILON);
        $this->assertEqualsWithDelta(1.0, $product->get(1, 1), EPSILON);
    }

    /**
     * Test inverse of a non-square matrix throws DomainException.
     */
    public function testInvNonSquareThrows(): void
    {
        $m = new Matrix(2, 3);
        $this->expectException(DomainException::class);
        $m->inv();
    }

    /**
     * Test inverse of a singular matrix throws DomainException.
     */
    public function testInvSingularMatrixThrows(): void
    {
        $m = Matrix::fromArray([
            [1, 2],
            [2, 4],
        ]);
        $this->expectException(DomainException::class);
        $m->inv();
    }

    /**
     * Test power with a positive exponent.
     */
    public function testPowPositive(): void
    {
        $m = Matrix::fromArray([
            [1, 1],
            [0, 1],
        ]);
        $result = $m->pow(3);
        // [[1,1],[0,1]]^3 = [[1,3],[0,1]]
        $this->assertEqualsWithDelta(1.0, $result->get(0, 0), EPSILON);
        $this->assertEqualsWithDelta(3.0, $result->get(0, 1), EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->get(1, 0), EPSILON);
        $this->assertEqualsWithDelta(1.0, $result->get(1, 1), EPSILON);
    }

    /**
     * Test power with zero exponent returns identity.
     */
    public function testPowZero(): void
    {
        $m = Matrix::fromArray([
            [2, 3],
            [4, 5],
        ]);
        $result = $m->pow(0);
        $this->assertEqualsWithDelta(1.0, $result->get(0, 0), EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->get(0, 1), EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->get(1, 0), EPSILON);
        $this->assertEqualsWithDelta(1.0, $result->get(1, 1), EPSILON);
    }

    /**
     * Test power with an exponent of 1 returns an equal but distinct instance (a clone, not $this).
     */
    public function testPowOne(): void
    {
        $m = Matrix::fromArray([
            [2, 3],
            [4, 5],
        ]);
        $result = $m->pow(1);

        $this->assertNotSame($m, $result);
        $this->assertTrue($m->equal($result));
    }

    /**
     * Test power with a negative exponent.
     */
    public function testPowNegative(): void
    {
        $m = Matrix::fromArray([
            [1, 1],
            [0, 1],
        ]);
        $result = $m->pow(-1);

        // Verify M * M^-1 = I.
        $product = $m->mul($result);
        $this->assertEqualsWithDelta(1.0, $product->get(0, 0), EPSILON);
        $this->assertEqualsWithDelta(0.0, $product->get(0, 1), EPSILON);
        $this->assertEqualsWithDelta(0.0, $product->get(1, 0), EPSILON);
        $this->assertEqualsWithDelta(1.0, $product->get(1, 1), EPSILON);
    }

    /**
     * Test power of a non-square matrix throws DomainException.
     */
    public function testPowNonSquareThrows(): void
    {
        $m = new Matrix(2, 3);
        $this->expectException(DomainException::class);
        $m->pow(2);
    }

    /**
     * Test sqr() squares a matrix.
     */
    public function testSqr(): void
    {
        // [[1, 2], [3, 4]]² = [[7, 10], [15, 22]]
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $result = $m->sqr();

        $expected = Matrix::fromArray([
            [7, 10],
            [15, 22],
        ]);
        $this->assertTrue($result->equal($expected));
    }

    /**
     * Test sqr() is equivalent to pow(2).
     */
    public function testSqrEqualsPowTwo(): void
    {
        $m = Matrix::fromArray([
            [2, 1],
            [0, 3],
        ]);
        $this->assertTrue($m->sqr()->equal($m->pow(2)));
    }

    /**
     * Test sqr() throws for non-square matrix.
     */
    public function testSqrNonSquareThrows(): void
    {
        $m = new Matrix(2, 3);
        $this->expectException(DomainException::class);
        $m->sqr();
    }
}
