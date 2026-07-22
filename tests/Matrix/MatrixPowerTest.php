<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use DomainException;
use OceanMoon\Math\Matrix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixPowerTest extends TestCase
{
    #region Method pow() tests.

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

    #endregion

    #region Method sqr() tests.

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

    #endregion
}
