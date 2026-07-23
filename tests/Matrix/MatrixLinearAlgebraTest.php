<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use DomainException;
use LengthException;
use OceanMoon\Math\Matrix;
use OceanMoon\Math\Vector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixLinearAlgebraTest extends TestCase
{
    #region Method mulVector() tests.

    /**
     * Test multiplying a matrix by a vector.
     */
    public function testMulVector(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $v = Vector::fromArray([1, 2, 3]);
        $result = $m->mulVector($v);
        // Row 0: 1*1+2*2+3*3=14
        // Row 1: 4*1+5*2+6*3=32
        $this->assertEqualsWithDelta(14.0, $result->toArray()[0], EPSILON);
        $this->assertEqualsWithDelta(32.0, $result->toArray()[1], EPSILON);
    }

    /**
     * Test multiplying a 0-row matrix by a vector returns a size-0 vector.
     */
    public function testMulVectorWithZeroRowMatrix(): void
    {
        $m = new Matrix(0, 3);
        $v = Vector::fromArray([1, 2, 3]);
        $result = $m->mulVector($v);
        $this->assertSame(0, $result->size);
    }

    /**
     * Test multiplying a 0-column matrix by an empty vector returns a zero vector matching the
     * matrix's row count. This exercises Vector::toColumnMatrix()'s handling of an empty vector,
     * which must produce a genuine n×1 (here 0×1) matrix rather than a 0×0 one, or the inner matrix
     * multiplication silently produces the wrong shape.
     */
    public function testMulVectorWithEmptyVectorAndZeroColumnMatrix(): void
    {
        $m = new Matrix(3, 0);
        $v = new Vector(0);
        $result = $m->mulVector($v);
        $this->assertSame(3, $result->size);
        $this->assertSame([0.0, 0.0, 0.0], $result->toArray());
    }

    /**
     * Test multiplying a matrix with a non-zero column count by an empty vector throws
     * LengthException, since the dimensions are genuinely incompatible.
     */
    public function testMulVectorWithIncompatibleSizeThrows(): void
    {
        $m = new Matrix(3, 2);
        $this->expectException(LengthException::class);
        $m->mulVector(new Vector(0));
    }

    #endregion

    #region Method t() tests.

    /**
     * Test transposing a matrix.
     */
    public function testTranspose(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $t = $m->t();
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

    #region Method det() tests.

    /**
     * Test determinant of a 1x1 matrix.
     */
    public function testDetOneByOne(): void
    {
        $m = Matrix::fromArray([
            [5],
        ]);
        $this->assertEqualsWithDelta(5.0, $m->det(), EPSILON);
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
        $this->assertEqualsWithDelta(-2.0, $m->det(), EPSILON);
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
        $this->assertEqualsWithDelta(-306.0, $m->det(), EPSILON);
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
        $this->assertEqualsWithDelta(-63.0, $m->det(), EPSILON);
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

    #region Method trace() tests.

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
}
