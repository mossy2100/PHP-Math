<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use OceanMoon\Math\Matrix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixNormTest extends TestCase
{
    #region Method norm() tests.

    /**
     * Test Frobenius norm of identity matrix.
     */
    public function testNormFrobeniusIdentity(): void
    {
        // Identity 3x3: sqrt(1+1+1) = sqrt(3)
        $this->assertEqualsWithDelta(sqrt(3), Matrix::identity(3)->norm(), EPSILON);
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
        $this->assertEqualsWithDelta(sqrt(30), $m->norm(), EPSILON);
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
        $this->assertEqualsWithDelta(sqrt(91), $m->norm(), EPSILON);
    }

    #endregion

    #region Method p1Norm() tests.

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

    #endregion

    #region Method pInfNorm() tests.

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

    #endregion

    #region norm()/p1Norm()/pInfNorm() zero matrix tests.

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
