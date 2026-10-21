<?php

declare(strict_types=1);

namespace Mossy2100\Math\Tests\Matrix;

use DomainException;
use LengthException;
use Mossy2100\Core\Exceptions\ArithmeticException;
use Mossy2100\Math\Matrix;
use Mossy2100\Math\Vector;
use OverflowException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TypeError;

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

    /**
     * Test add() throws when the result overflows.
     */
    public function testAddOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot add the given Matrices. The result would overflow.');
        Matrix::fromArray([
            [1e308],
        ])->add(Matrix::fromArray([
            [1e308],
        ]));
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

    /**
     * Test sub() throws when the result overflows.
     */
    public function testSubOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot subtract the given Matrices. The result would overflow.');
        Matrix::fromArray([
            [1e308],
        ])->sub(Matrix::fromArray([
            [-1e308],
        ]));
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

    /**
     * Test multiplying a matrix by a vector.
     */
    public function testMulByVector(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $v = Vector::fromArray([1, 2, 3]);
        $result = $m->mul($v);
        // Row 0: 1*1+2*2+3*3=14
        // Row 1: 4*1+5*2+6*3=32
        $this->assertEqualsWithDelta(14.0, $result->toArray()[0], EPSILON);
        $this->assertEqualsWithDelta(32.0, $result->toArray()[1], EPSILON);
    }

    /**
     * Test multiplying a 0-row matrix by a vector returns a count-0 vector.
     */
    public function testMulByVectorWithZeroRowMatrix(): void
    {
        $m = new Matrix(0, 3);
        $v = Vector::fromArray([1, 2, 3]);
        $result = $m->mul($v);
        $this->assertSame(0, $result->count);
    }

    /**
     * Test multiplying a 0-column matrix by an empty vector returns a zero vector matching the matrix's row
     * count. Each row of a 0-column matrix is itself a count-0 Vector, so this exercises Vector::dot() between
     * two count-0 vectors, which must return 0.0 rather than erroring, so the result has the right shape and
     * values rather than being empty or malformed.
     */
    public function testMulByVectorWithEmptyVectorAndZeroColumnMatrix(): void
    {
        $m = new Matrix(3, 0);
        $v = new Vector(0);
        $result = $m->mul($v);
        $this->assertSame(3, $result->count);
        $this->assertSame([0.0, 0.0, 0.0], $result->toArray());
    }

    /**
     * Test multiplying a matrix with a non-zero column count by an empty vector throws
     * LengthException, since the dimensions are genuinely incompatible.
     */
    public function testMulByVectorWithIncompatibleCountThrows(): void
    {
        $m = new Matrix(3, 2);
        $this->expectException(LengthException::class);
        $m->mul(new Vector(0));
    }

    /**
     * Test mul() by a scalar throws when the result overflows.
     */
    public function testMulByScalarOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot multiply the given Matrix by 1.0E+200. The result would overflow.');
        Matrix::fromArray([
            [1e200],
        ])->mul(1e200);
    }

    /**
     * Test mul() by a non-finite scalar throws, even for an empty matrix.
     */
    public function testMulByNonFiniteScalarThrows(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid scalar: NAN. Must be finite.');
        new Matrix(0, 0)->mul(NAN);
    }

    /**
     * Test mul() by a vector throws when the result overflows.
     */
    public function testMulByVectorOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot multiply the given Matrix and Vector. The result would overflow.');
        Matrix::fromArray([
            [1e200],
        ])->mul(Vector::fromArray([1e200]));
    }

    /**
     * Test mul() by a matrix throws when the result overflows.
     */
    public function testMulByMatrixOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot multiply the given Matrices. The result would overflow.');
        Matrix::fromArray([
            [1e200],
        ])->mul(Matrix::fromArray([
            [1e200],
        ]));
    }

    /**
     * Test mul() with an argument of the wrong type throws TypeError, naming the accepted types.
     */
    public function testMulInvalidTypeThrows(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage(
            'must be of type Mossy2100\\Math\\Vector|Mossy2100\\Math\\Matrix|float, string given'
        );

        $x = Matrix::fromArray([
            [1, 2],
        ]);
        $x->mul('x'); // @phpstan-ignore argument.type
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
     * Test dividing a matrix by a non-finite scalar throws DomainException, rather than silently giving zeros.
     */
    public function testDivByNonFiniteThrows(): void
    {
        $m = new Matrix(2, 2);
        foreach ([INF, -INF, NAN] as $scalar) {
            try {
                $m->div($scalar);
                $this->fail('Expected DomainException was not thrown for ' . var_export($scalar, true) . '.');
            } catch (DomainException $e) {
                $this->assertStringEndsWith('. Must be finite.', $e->getMessage());
            }
        }
    }

    /**
     * Test div() throws when the result overflows.
     */
    public function testDivOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot divide the given Matrix by 1.0E-200. The result would overflow.');
        Matrix::fromArray([
            [1e200],
        ])->div(1e-200);
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

    /**
     * Test hadamardMul() throws when the result overflows.
     */
    public function testHadamardMulOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage(
            'Cannot compute Hadamard product of the given Matrices. The result would overflow.'
        );
        Matrix::fromArray([
            [1e200],
        ])->hadamardMul(Matrix::fromArray([
            [1e200],
        ]));
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

    /**
     * Test hadamardDiv() throws when the result overflows.
     */
    public function testHadamardDivOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage(
            'Cannot compute Hadamard quotient of the given Matrices. The result would overflow.'
        );
        Matrix::fromArray([
            [1e200],
        ])->hadamardDiv(Matrix::fromArray([
            [1e-200],
        ]));
    }

    #endregion
}
