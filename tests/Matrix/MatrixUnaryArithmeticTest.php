<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use DomainException;
use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Math\Matrix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixUnaryArithmeticTest extends TestCase
{
    #region Method neg() tests.

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

    #endregion

    #region Method reciprocal() tests.

    /**
     * Test the element-wise reciprocal of a matrix.
     */
    public function testReciprocal(): void
    {
        $m = Matrix::fromArray([
            [2, 4],
            [5, 10],
        ]);
        $result = $m->reciprocal();
        $this->assertEqualsWithDelta(0.5, $result->get(0, 0), EPSILON);
        $this->assertEqualsWithDelta(0.25, $result->get(0, 1), EPSILON);
        $this->assertEqualsWithDelta(0.2, $result->get(1, 0), EPSILON);
        $this->assertEqualsWithDelta(0.1, $result->get(1, 1), EPSILON);
    }

    /**
     * Test the reciprocal of a matrix containing a zero element throws ArithmeticException.
     */
    public function testReciprocalOfZeroElementThrows(): void
    {
        $m = Matrix::fromArray([
            [1, 0],
            [2, 3],
        ]);
        $this->expectException(ArithmeticException::class);
        $m->reciprocal();
    }

    #endregion

    #region Method inv() tests.

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

    #endregion
}
