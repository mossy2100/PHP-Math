<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use DomainException;
use LengthException;
use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Math\Matrix;
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
     * Test that pow(1)'s clone has independent row data: mutating a row of the clone (as returned by pow(1), which
     * relies on Matrix's __clone()) does not affect the original.
     */
    public function testPowOneCloneIsIndependent(): void
    {
        $m = Matrix::fromArray([
            [2, 3],
            [4, 5],
        ]);
        $result = $m->pow(1);
        $result->set(0, 0, 999);

        $this->assertSame(2.0, $m->get(0, 0));
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
     * Test power with exponent PHP_INT_MIN doesn't overflow when negating the exponent.
     *
     * Negative exponents are normally handled via inv()->pow(-$exponent), but negating PHP_INT_MIN overflows to a
     * float in PHP, which would previously cause a TypeError when passed to the int-typed recursive pow() call. The
     * identity matrix is used as the base because pow() operates on float elements via repeated matrix
     * multiplication (no OverflowException is possible), so any other base raised to this exponent would produce a
     * numerically meaningless result (float overflow to INF, or a value with no useful precision) rather than
     * something this test could assert against exactly.
     */
    public function testPowIntMinExponent(): void
    {
        $identity = Matrix::identity(2);
        $result = $identity->pow(PHP_INT_MIN);

        $this->assertTrue($identity->equal($result));
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
