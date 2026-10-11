<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use DomainException;
use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Core\Traits\Asserts\FloatAssertions;
use OceanMoon\Math\Matrix;
use OverflowException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixUnaryArithmeticTest extends TestCase
{
    use FloatAssertions;

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

    /**
     * Test reciprocal() throws when the result overflows.
     */
    public function testReciprocalOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot compute reciprocal of Matrix. The result would overflow.');
        Matrix::fromArray([
            [1e-320],
        ])->reciprocal();
    }

    #endregion

    #region Method inv() tests.

    /**
     * Test inverse of a 2x2 matrix.
     */
    public function testInvTwoByTwo(): void
    {
        $m = Matrix::fromArray([
            [4, 7],
            [2, 6],
        ]);
        $inv = $m->inv();

        // Verify A * A^-1 = I.
        $product = $m->mul($inv);
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
     * Data for matrices whose determinant would underflow or overflow, though the inverse is representable.
     *
     * @return array<string, array{float}> The scale of the elements.
     */
    public static function invExtremeProvider(): array
    {
        return [
            'tiny' => [1e-200],
            'huge' => [1e200],
        ];
    }

    /**
     * Test inv() of a matrix with uniformly tiny or huge elements, whose determinant would underflow or overflow.
     */
    #[DataProvider('invExtremeProvider')]
    public function testInvExtreme(float $scale): void
    {
        // [[4, 7], [2, 6]] has determinant 10 and inverse [[0.6, -0.7], [-0.2, 0.4]].
        $m = Matrix::fromArray([
            [
                4 * $scale,
                7 * $scale,
            ],
            [
                2 * $scale,
                6 * $scale,
            ],
        ]);
        $inv = $m->inv();

        $this->assertApproxEqual(0.6 / $scale, $inv->get(0, 0));
        $this->assertApproxEqual(-0.7 / $scale, $inv->get(0, 1));
        $this->assertApproxEqual(-0.2 / $scale, $inv->get(1, 0));
        $this->assertApproxEqual(0.4 / $scale, $inv->get(1, 1));
    }

    /**
     * Test inv() of a matrix whose elements span a wide range of magnitudes.
     *
     * Its determinant is 1, but scaling the elements so the largest is about 1 would make the smallest underflow to
     * 0, and the determinant with it.
     */
    public function testInvMixedMagnitude(): void
    {
        $m = Matrix::fromArray([
            [1e-300, 0],
            [0, 1e300],
        ]);
        $inv = $m->inv();

        $this->assertApproxEqual(1e300, $inv->get(0, 0));
        $this->assertSame(0.0, abs($inv->get(0, 1)));
        $this->assertSame(0.0, abs($inv->get(1, 0)));
        $this->assertApproxEqual(1e-300, $inv->get(1, 1));
    }

    /**
     * Test inv() of an empty matrix is an empty matrix, and of a zero matrix throws.
     */
    public function testInvEmptyAndZero(): void
    {
        $this->assertSame([], new Matrix(0, 0)->inv()->toArray());

        $this->expectException(ArithmeticException::class);
        new Matrix(2, 2)->inv();
    }

    /**
     * Test inv() throws when the result overflows.
     */
    public function testInvOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot invert Matrix. The result would overflow.');
        Matrix::fromArray([
            [1e-310],
        ])->inv();
    }

    /**
     * Test inv() throws for a matrix that's singular in exact arithmetic, but whose determinant comes out tiny
     * rather than zero in floating point.
     */
    public function testInvNearSingularThrows(): void
    {
        $m = Matrix::fromArray([
            [0.1, 0.3],
            [0.7, 2.1],
        ]);

        $this->expectException(ArithmeticException::class);
        $this->expectExceptionMessage('Cannot invert singular Matrix.');
        $m->inv();
    }

    /**
     * Test inv() throws for a singular matrix larger than 3×3, inverted by LU decomposition.
     */
    public function testInvLargeSingularThrows(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3, 4],
            [2, 4, 6, 8],
            [1, 0, 1, 0],
            [0, 1, 0, 1],
        ]);

        $this->expectException(ArithmeticException::class);
        $this->expectExceptionMessage('Cannot invert singular Matrix.');
        $m->inv();
    }

    /**
     * Test inv() of a matrix larger than 3×3, inverted by LU decomposition: A × A⁻¹ = I.
     */
    public function testInvLarge(): void
    {
        $m = Matrix::fromArray([
            [4, 1, 0, 0, 2],
            [1, 5, 1, 0, 0],
            [0, 1, 6, 1, 0],
            [0, 0, 1, 7, 1],
            [2, 0, 0, 1, 8],
        ]);

        $this->assertTrue($m->mul($m->inv())->approxEqual(Matrix::identity(5), absTol: 1e-12));
    }

    /**
     * Test inv() of a matrix larger than 3×3 with zeros on the diagonal, which needs row swaps.
     */
    public function testInvLargeNeedsPivoting(): void
    {
        // A permutation matrix's inverse is its transpose.
        $m = Matrix::fromArray([
            [0, 1, 0, 0],
            [0, 0, 0, 1],
            [1, 0, 0, 0],
            [0, 0, 1, 0],
        ]);

        $this->assertTrue($m->inv()->equal($m->t()));
    }

    /**
     * Test inv() of a matrix larger than 3×3 whose rows differ widely in scale.
     */
    public function testInvLargeMixedMagnitude(): void
    {
        $m = Matrix::fromArray([
            [1e-300, 0, 0, 0],
            [0, 2, 0, 0],
            [0, 0, 4, 0],
            [0, 0, 0, 1e300],
        ]);
        $inv = $m->inv();

        $this->assertApproxEqual(1e300, $inv->get(0, 0));
        $this->assertSame(0.5, $inv->get(1, 1));
        $this->assertSame(0.25, $inv->get(2, 2));
        $this->assertApproxEqual(1e-300, $inv->get(3, 3));
    }

    #endregion
}
