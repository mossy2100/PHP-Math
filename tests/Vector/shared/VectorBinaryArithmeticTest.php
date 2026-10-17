<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Vector;

use DomainException;
use LengthException;
use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Math\Matrix;
use OceanMoon\Math\Vector;
use OverflowException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TypeError;

#[CoversClass(Vector::class)]
class VectorBinaryArithmeticTest extends TestCase
{
    #region Method add() tests.

    /**
     * Test adding two vectors.
     */
    public function testAdd(): void
    {
        $v1 = Vector::fromArray([1, 2, 3]);
        $v2 = Vector::fromArray([4, 5, 6]);
        $result = $v1->add($v2);
        $this->assertSame([5.0, 7.0, 9.0], $result->toArray());
    }

    /**
     * Test adding vectors with different sizes throws LengthException.
     */
    public function testAddWithDifferentSizesThrows(): void
    {
        $v1 = Vector::fromArray([1, 2]);
        $v2 = Vector::fromArray([1, 2, 3]);
        $this->expectException(LengthException::class);
        $v1->add($v2);
    }

    /**
     * Test add() throws when the result overflows.
     */
    public function testAddOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot add the given Vectors. The result would overflow.');
        Vector::fromArray([1e308])->add(Vector::fromArray([1e308]));
    }

    #endregion

    #region Method sub() tests.

    /**
     * Test subtracting two vectors.
     */
    public function testSub(): void
    {
        $v1 = Vector::fromArray([10, 20, 30]);
        $v2 = Vector::fromArray([1, 2, 3]);
        $result = $v1->sub($v2);
        $this->assertSame([9.0, 18.0, 27.0], $result->toArray());
    }

    /**
     * Test subtracting vectors with different sizes throws LengthException.
     */
    public function testSubWithDifferentSizesThrows(): void
    {
        $v1 = Vector::fromArray([1, 2, 3]);
        $v2 = Vector::fromArray([1, 2]);
        $this->expectException(LengthException::class);
        $v1->sub($v2);
    }

    /**
     * Test sub() throws when the result overflows.
     */
    public function testSubOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot subtract the given Vectors. The result would overflow.');
        Vector::fromArray([1e308])->sub(Vector::fromArray([-1e308]));
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
     * Test multiplying a vector by a Matrix. The vector is treated as a row vector (xA); each element of the
     * result is the dot product of the vector with the corresponding column of the matrix.
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
        $this->expectExceptionMessage("Invalid Matrix row count: 2. Must equal this Vector's count: 3.");
        $v->mul($m);
    }

    /**
     * Test multiplying a vector by a zero-row matrix with an incompatible count throws LengthException. The
     * dimension check must fire even though a zero-row matrix's multiplication loop body never executes, so this
     * case wouldn't otherwise be caught.
     */
    public function testMulByZeroRowMatrixIncompatibleDimensionsThrows(): void
    {
        $v = Vector::fromArray([1, 2]);
        $m = new Matrix(0, 3);

        $this->expectException(LengthException::class);
        $v->mul($m);
    }

    /**
     * Test multiplying a vector by a zero-column matrix with a matching row count returns a count-0 vector.
     */
    public function testMulByZeroColumnMatrix(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $m = new Matrix(3, 0);

        $result = $v->mul($m);

        $this->assertSame(0, $result->count);
    }

    /**
     * Test multiplying by a vector is the Hadamard (element-wise) product.
     */
    public function testMulByVector(): void
    {
        $v1 = Vector::fromArray([1, 2, 3]);
        $v2 = Vector::fromArray([4, 5, 6]);
        $this->assertSame([4.0, 10.0, 18.0], $v1->mul($v2)->toArray());
    }

    /**
     * Test multiplying by a vector with a different count throws LengthException.
     */
    public function testMulByVectorWithDifferentCountsThrows(): void
    {
        $v1 = Vector::fromArray([1, 2, 3]);
        $v2 = Vector::fromArray([1, 2]);
        $this->expectException(LengthException::class);
        $this->expectExceptionMessage('Cannot multiply by Vector of incorrect count: 2. Expected 3.');
        $v1->mul($v2);
    }

    /**
     * Test mul() by a scalar throws when the result overflows.
     */
    public function testMulByScalarOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot multiply the given Vector by 1.0E+200. The result would overflow.');
        Vector::fromArray([1e200])->mul(1e200);
    }

    /**
     * Test mul() by a non-finite scalar throws, even for an empty vector.
     */
    public function testMulByNonFiniteScalarThrows(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid scalar: INF. Must be finite.');
        new Vector(0)->mul(INF);
    }

    /**
     * Test mul() by a vector throws when the result overflows.
     */
    public function testMulByVectorOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot multiply the given Vectors. The result would overflow.');
        Vector::fromArray([1e200])->mul(Vector::fromArray([1e200]));
    }

    /**
     * Test mul() by a matrix throws when the result overflows.
     */
    public function testMulByMatrixOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot multiply the given Vector and Matrix. The result would overflow.');
        Vector::fromArray([1e200])->mul(Matrix::fromArray([
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
            'must be of type OceanMoon\\Math\\Vector|OceanMoon\\Math\\Matrix|float, string given'
        );

        $x = Vector::fromArray([1, 2]);
        $x->mul('x'); // @phpstan-ignore argument.type
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

    /**
     * Test dividing a vector by a non-finite scalar throws DomainException, rather than silently giving zeros.
     */
    public function testDivByNonFiniteThrows(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        foreach ([INF, -INF, NAN] as $scalar) {
            try {
                $v->div($scalar);
                $this->fail('Expected DomainException was not thrown for ' . var_export($scalar, true) . '.');
            } catch (DomainException $e) {
                $this->assertStringEndsWith('. Must be finite.', $e->getMessage());
            }
        }
    }

    /**
     * Test dividing by a vector is Hadamard (element-wise) division.
     */
    public function testDivByVector(): void
    {
        $v1 = Vector::fromArray([4, 10, 18]);
        $v2 = Vector::fromArray([4, 5, 6]);
        $this->assertSame([1.0, 2.0, 3.0], $v1->div($v2)->toArray());
    }

    /**
     * Test dividing by a vector with a different count throws LengthException.
     */
    public function testDivByVectorWithDifferentCountsThrows(): void
    {
        $v1 = Vector::fromArray([1, 2, 3]);
        $v2 = Vector::fromArray([1, 2]);
        $this->expectException(LengthException::class);
        $this->expectExceptionMessage('Cannot divide by Vector of incorrect count: 2. Expected 3.');
        $v1->div($v2);
    }

    /**
     * Test dividing by a vector with a zero element throws ArithmeticException.
     */
    public function testDivByVectorWithZeroElementThrows(): void
    {
        $v1 = Vector::fromArray([1, 2, 3]);
        $v2 = Vector::fromArray([1, 0, 1]);
        $this->expectException(ArithmeticException::class);
        $this->expectExceptionMessage('Cannot divide by zero at index 1.');
        $v1->div($v2);
    }

    /**
     * Test div() by a scalar throws when the result overflows.
     */
    public function testDivByScalarOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot divide the given Vector by 1.0E-200. The result would overflow.');
        Vector::fromArray([1e200])->div(1e-200);
    }

    /**
     * Test div() by a vector throws when the result overflows.
     */
    public function testDivByVectorOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot divide the given Vectors. The result would overflow.');
        Vector::fromArray([1e200])->div(Vector::fromArray([1e-200]));
    }

    /**
     * Test div() with an argument of the wrong type throws TypeError, naming the accepted types.
     */
    public function testDivInvalidTypeThrows(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('must be of type OceanMoon\\Math\\Vector|float, string given');

        $x = Vector::fromArray([1, 2]);
        $x->div('x'); // @phpstan-ignore argument.type
    }

    #endregion
}
