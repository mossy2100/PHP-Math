<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use DomainException;
use OceanMoon\Core\Traits\Asserts\FloatAssertions;
use OceanMoon\Math\Matrix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixLinearAlgebraTest extends TestCase
{
    use FloatAssertions;

    #region transpose() tests

    /**
     * Test transposing a matrix.
     */
    public function testTranspose(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $t = $m->transpose();
        $this->assertSame(3, $t->rowCount);
        $this->assertSame(2, $t->columnCount);
        $this->assertSame(1.0, $t->get(0, 0));
        $this->assertSame(2.0, $t->get(1, 0));
        $this->assertSame(3.0, $t->get(2, 0));
        $this->assertSame(4.0, $t->get(0, 1));
        $this->assertSame(5.0, $t->get(1, 1));
        $this->assertSame(6.0, $t->get(2, 1));
    }

    #endregion

    #region det() tests

    /**
     * Test determinant of a 1x1 matrix.
     */
    public function testDetOneByOne(): void
    {
        $m = Matrix::fromArray([
            [5],
        ]);
        $this->assertEqualsWithDelta(5.0, $m->det(), 1e-10);
    }

    /**
     * Test determinant of a 2x2 matrix.
     */
    public function testDetTwoByTwo(): void
    {
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        // det = 1*4 - 2*3 = -2
        $this->assertEqualsWithDelta(-2.0, $m->det(), 1e-10);
    }

    /**
     * Test determinant of a 3x3 matrix.
     */
    public function testDetThreeByThree(): void
    {
        $m = Matrix::fromArray([
            [6, 1, 1],
            [4, -2, 5],
            [2, 8, 7],
        ]);
        // det = 6(-2*7 - 5*8) - 1(4*7 - 5*2) + 1(4*8 - (-2)*2)
        //     = 6(-14-40) - 1(28-10) + 1(32+4)
        //     = 6(-54) - 18 + 36 = -324 - 18 + 36 = -306
        $this->assertEqualsWithDelta(-306.0, $m->det(), 1e-10);
    }

    /**
     * Test determinant of a 4x4 matrix. This is the smallest size that exercises the recursive cofactor-expansion
     * branch of calcDet(), since 1x1, 2x2, and 3x3 are all handled directly via closed-form formulas and never reach
     * it.
     */
    public function testDetFourByFour(): void
    {
        $m = Matrix::fromArray([
            [2, 0, 1, 3],
            [1, 3, 2, 0],
            [0, 1, 4, 1],
            [5, 2, 0, 2],
        ]);
        // Verified via cofactor expansion along row 0 and independently via row reduction to
        // upper-triangular form (product of pivots 2, 3, 3.5, -3): det = -63.
        $this->assertEqualsWithDelta(-63.0, $m->det(), 1e-10);
    }

    /**
     * Test determinant of a non-square matrix throws DomainException.
     */
    public function testDetNonSquareThrows(): void
    {
        $m = new Matrix(2, 3);
        $this->expectException(DomainException::class);
        $m->det();
    }

    #endregion

    #region trace() tests

    /**
     * Test trace of identity matrix.
     */
    public function testTraceIdentity(): void
    {
        $this->assertSame(3.0, Matrix::identity(3)->trace());
    }

    /**
     * Test trace of a 2x2 matrix.
     */
    public function testTraceTwoByTwo(): void
    {
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $this->assertSame(5.0, $m->trace());
    }

    /**
     * Test trace of a 3x3 matrix.
     */
    public function testTraceThreeByThree(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9],
        ]);
        $this->assertSame(15.0, $m->trace());
    }

    /**
     * Test trace of a 1x1 matrix.
     */
    public function testTraceOneByOne(): void
    {
        $m = Matrix::fromArray([
            [7],
        ]);
        $this->assertSame(7.0, $m->trace());
    }

    /**
     * Test trace of a zero matrix.
     */
    public function testTraceZeroMatrix(): void
    {
        $m = new Matrix(3, 3);
        $this->assertSame(0.0, $m->trace());
    }

    /**
     * Test trace of non-square matrix throws.
     */
    public function testTraceNonSquareThrows(): void
    {
        $m = new Matrix(2, 3);
        $this->expectException(DomainException::class);
        $m->trace();
    }

    #endregion

    #region norm() tests

    /**
     * Test Frobenius norm of identity matrix.
     */
    public function testNormFrobeniusIdentity(): void
    {
        // Identity 3x3: sqrt(1+1+1) = sqrt(3)
        $this->assertApproxEqual(sqrt(3), Matrix::identity(3)->norm());
    }

    /**
     * Test Frobenius norm of a simple matrix.
     */
    public function testNormFrobeniusSimple(): void
    {
        // [[1, 2], [3, 4]]: sqrt(1+4+9+16) = sqrt(30)
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $this->assertApproxEqual(sqrt(30), $m->norm());
    }

    /**
     * Test Frobenius norm of a non-square matrix.
     */
    public function testNormFrobeniusNonSquare(): void
    {
        // [[1, 2, 3], [4, 5, 6]]: sqrt(1+4+9+16+25+36) = sqrt(91)
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $this->assertApproxEqual(sqrt(91), $m->norm());
    }

    /**
     * Test P1 norm (max absolute column sum).
     */
    public function testNormP1(): void
    {
        // [[1, -2], [3, 4]]: col0 = |1|+|3| = 4, col1 = |-2|+|4| = 6 => 6
        $m = Matrix::fromArray([
            [1, -2],
            [3, 4],
        ]);
        $this->assertSame(6.0, $m->p1Norm());
    }

    /**
     * Test P-infinity norm (max absolute row sum).
     */
    public function testNormPInf(): void
    {
        // [[1, -2], [3, 4]]: row0 = |1|+|-2| = 3, row1 = |3|+|4| = 7 => 7
        $m = Matrix::fromArray([
            [1, -2],
            [3, 4],
        ]);
        $this->assertSame(7.0, $m->pInfNorm());
    }

    /**
     * Test norm of zero matrix is zero.
     */
    public function testNormZeroMatrix(): void
    {
        $m = new Matrix(2, 2);
        $this->assertSame(0.0, $m->norm());
        $this->assertSame(0.0, $m->p1Norm());
        $this->assertSame(0.0, $m->pInfNorm());
    }

    #endregion
}
