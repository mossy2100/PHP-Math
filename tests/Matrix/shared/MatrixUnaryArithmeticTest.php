<?php

declare(strict_types=1);

namespace Mossy2100\Math\Tests\Matrix;

use DomainException;
use Mossy2100\Core\Exceptions\ArithmeticException;
use Mossy2100\Core\Floats;
use Mossy2100\Core\Traits\Asserts\FloatAssertions;
use Mossy2100\Math\Matrix;
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
        $m = Matrix::fromArray([[1, -2], [3, -4]]);
        $result = $m->neg();
        $this->assertSame([[-1.0, 2.0], [-3.0, 4.0]], $result->toArray());
    }

    /**
     * Test negating a zero matrix returns a zero matrix.
     */
    public function testNegZeroMatrix(): void
    {
        $m = new Matrix(2, 2);
        $result = $m->neg();
        $this->assertSame([[0.0, 0.0], [0.0, 0.0]], $result->toArray());
    }

    /**
     * Test negating zeros doesn't give negative zeros. assertSame() treats -0.0 and 0.0 as equal, so compare the
     * strings, where -0.0 is "-0".
     */
    public function testNegHasNoNegativeZeros(): void
    {
        $result = Matrix::fromArray([[0, 1]])->neg();
        $this->assertSame(['0', '-1'], array_map(strval(...), $result->toArray()[0]));
    }

    #endregion

    #region Method hadamardInv() tests.

    /**
     * Test the Hadamard inverse (element-wise reciprocal) of a matrix.
     */
    public function testHadamardInv(): void
    {
        $m = Matrix::fromArray([[2, 4], [5, 10]]);
        $result = $m->hadamardInv();
        $this->assertEqualsWithDelta(0.5, $result->get(0, 0), EPSILON);
        $this->assertEqualsWithDelta(0.25, $result->get(0, 1), EPSILON);
        $this->assertEqualsWithDelta(0.2, $result->get(1, 0), EPSILON);
        $this->assertEqualsWithDelta(0.1, $result->get(1, 1), EPSILON);
    }

    /**
     * Test the Hadamard inverse of a matrix containing a zero element throws ArithmeticException.
     */
    public function testHadamardInvOfZeroElementThrows(): void
    {
        $m = Matrix::fromArray([[1, 0], [2, 3]]);
        $this->expectException(ArithmeticException::class);
        $m->hadamardInv();
    }

    /**
     * Test hadamardInv() throws when the result overflows.
     */
    public function testHadamardInvOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot compute reciprocal of the given Matrix. The result would overflow.');
        Matrix::fromArray([[1e-320]])->hadamardInv();
    }

    #endregion

    #region Method inv() tests.

    /**
     * Test inverse of a 2x2 matrix.
     */
    public function testInvTwoByTwo(): void
    {
        $m = Matrix::fromArray([[4, 7], [2, 6]]);
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
        $m = Matrix::fromArray([[1, 2], [2, 4]]);
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
        return ['tiny' => [1e-200], 'huge' => [1e200]];
    }

    /**
     * Test inv() of a matrix with uniformly tiny or huge elements, whose determinant would underflow or overflow.
     */
    #[DataProvider('invExtremeProvider')]
    public function testInvExtreme(float $scale): void
    {
        // [[4, 7], [2, 6]] has determinant 10 and inverse [[0.6, -0.7], [-0.2, 0.4]].
        $m = Matrix::fromArray([[4 * $scale, 7 * $scale], [2 * $scale, 6 * $scale]]);
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
        $m = Matrix::fromArray([[1e-300, 0], [0, 1e300]]);
        $inv = $m->inv();

        $this->assertApproxEqual(1e300, $inv->get(0, 0));
        $this->assertSame(0.0, abs($inv->get(0, 1)));
        $this->assertSame(0.0, abs($inv->get(1, 0)));
        $this->assertApproxEqual(1e-300, $inv->get(1, 1));
    }

    /**
     * Test inv() of a 3×3 matrix whose determinant underflows to 0, even after scaling, though it's invertible.
     *
     * The adjugate formula needs the determinant, so the inverse comes from the LU decomposition instead.
     */
    public function testInvDeterminantUnderflows(): void
    {
        $m = Matrix::fromArray([[1, 0, 0], [0, 1e-200, 0], [0, 0, 1e-200]]);
        $inv = $m->inv();

        $this->assertSame(1.0, $inv->get(0, 0));
        $this->assertApproxEqual(1e200, $inv->get(1, 1));
        $this->assertApproxEqual(1e200, $inv->get(2, 2));
        $this->assertSame(0.0, $inv->get(0, 1));
        $this->assertSame(0.0, $inv->get(1, 2));
    }

    /**
     * Test inv() of a matrix of the largest floats, whose determinant overflows, so it's scaled first. The scale must
     * be a finite power of 2, though log2(PHP_FLOAT_MAX) rounds up to 1024.
     */
    public function testInvLargestFloats(): void
    {
        $m = Matrix::fromArray([[PHP_FLOAT_MAX, 0], [0, PHP_FLOAT_MAX]]);
        $inv = $m->inv();

        $this->assertSame(1 / PHP_FLOAT_MAX, $inv->get(0, 0));
        $this->assertSame(1 / PHP_FLOAT_MAX, $inv->get(1, 1));
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
        $this->expectExceptionMessage('Cannot invert the given Matrix. The result would overflow.');
        Matrix::fromArray([[1e-310]])->inv();
    }

    /**
     * Test inv() throws for a matrix that's singular in exact arithmetic, but whose determinant comes out tiny
     * rather than zero in floating point.
     */
    public function testInvNearSingularThrows(): void
    {
        $m = Matrix::fromArray([[0.1, 0.3], [0.7, 2.1]]);

        $this->expectException(ArithmeticException::class);
        $this->expectExceptionMessage('Cannot invert singular Matrix.');
        $m->inv();
    }

    /**
     * Data provider for testInvBadlyScaledColumns(): invertible matrices whose columns differ widely in scale, with
     * their exact inverses.
     *
     * @return array<string, array{list<list<float>>, list<list<float>>}>
     */
    public static function invBadlyScaledColumnsProvider(): array
    {
        return [
            'shear' => [[[1, 1e20], [0, 1]], [[1, -1e20], [0, 1]]],
            'swapped' => [[[1, 1e16], [1, 0]], [[0, 1], [1e-16, -1e-16]]],
            'shear, 4×4' => [
                [[1, 1e20, 0, 0], [0, 1, 0, 0], [0, 0, 1, 0], [0, 0, 0, 1]],
                [[1, -1e20, 0, 0], [0, 1, 0, 0], [0, 0, 1, 0], [0, 0, 0, 1]],
            ],
        ];
    }

    /**
     * Test inv() of a matrix that's only badly scaled by column isn't mistaken for a singular one.
     *
     * @param list<list<float>> $data The matrix.
     * @param list<list<float>> $expected Its inverse.
     */
    #[DataProvider('invBadlyScaledColumnsProvider')]
    public function testInvBadlyScaledColumns(array $data, array $expected): void
    {
        $this->assertTrue(Matrix::fromArray($data)->inv()->approxEqual(Matrix::fromArray($expected), absTol: 0.0));
    }

    /**
     * Test inv() still rejects a singular matrix after its columns are scaled, e.g. one with a column far larger than
     * the others, or a zero column.
     *
     * @param list<list<float>> $data The matrix.
     */
    #[DataProvider('invSingularScaledColumnsProvider')]
    public function testInvSingularScaledColumnsThrows(array $data): void
    {
        $this->expectException(ArithmeticException::class);
        $this->expectExceptionMessage('Cannot invert singular Matrix.');
        Matrix::fromArray($data)->inv();
    }

    /**
     * Data provider for testInvSingularScaledColumnsThrows().
     *
     * @return array<string, array{list<list<float>>}>
     */
    public static function invSingularScaledColumnsProvider(): array
    {
        return [
            'rank 2, large column' => [[[1, 2e10, 3], [4, 5e10, 6], [7, 8e10, 9]]],
            'zero column, 4×4' => [[[1, 0, 2, 3], [4, 0, 5, 6], [7, 0, 8, 9], [1, 0, 1, 1]]],
        ];
    }

    /**
     * Test inv() throws for a singular matrix larger than 3×3, inverted by LU decomposition.
     */
    public function testInvLargeSingularThrows(): void
    {
        $m = Matrix::fromArray([[1, 2, 3, 4], [2, 4, 6, 8], [1, 0, 1, 0], [0, 1, 0, 1]]);

        $this->expectException(ArithmeticException::class);
        $this->expectExceptionMessage('Cannot invert singular Matrix.');
        $m->inv();
    }

    /**
     * Data provider for testInvSingularAfterRoundingThrows(): singular matrices whose pivots all pass the pivot test,
     * since rounding during elimination leaves the last one around 1e-15 rather than 0.
     *
     * @return array<string, array{list<list<int>>}>
     */
    public static function singularAfterRoundingProvider(): array
    {
        return [
            '4×4, row 3 = row 0 - 2 × row 1' => [[[2, -4, 3, -4], [-2, 1, -5, -3], [1, 5, 5, -1], [6, -6, 13, 2]]],
            '5×5, row 4 = 2 × row 0 + row 3' => [
                [[-1, -3, 5, -4, -5], [3, 4, 3, 3, -5], [-5, -4, -1, -2, 0], [5, -2, -5, -5, 5], [3, -8, 5, -13, -5]],
            ],
            '6×6, row 5 = row 0 + row 1' => [
                [
                    [-2, 3, -2, 2, 1, -4],
                    [1, 0, -3, 5, 1, -4],
                    [5, -1, -3, -1, -3, 2],
                    [0, 4, -5, 5, 0, -1],
                    [-4, 4, 4, 1, 0, -3],
                    [-1, 3, -5, 7, 2, -8],
                ],
            ],
        ];
    }

    /**
     * Test inv() throws for a singular matrix that passes the pivot test, which the condition number check catches.
     *
     * @param list<list<int>> $rows The matrix.
     */
    #[DataProvider('singularAfterRoundingProvider')]
    public function testInvSingularAfterRoundingThrows(array $rows): void
    {
        $this->expectException(ArithmeticException::class);
        $this->expectExceptionMessage('Cannot invert singular Matrix.');
        Matrix::fromArray($rows)->inv();
    }

    /**
     * Test inv() inverts the 11×11 Hilbert matrix, but rejects the 12×12 one, whose condition number of about 1e16
     * leaves its inverse with no correct digits.
     */
    public function testInvHilbert(): void
    {
        $hilbert = static function (int $n): Matrix {
            $rows = [];
            for ($i = 0; $i < $n; $i++) {
                for ($j = 0; $j < $n; $j++) {
                    $rows[$i][$j] = 1 / ($i + $j + 1);
                }
            }
            return Matrix::fromArray($rows);
        };

        $this->assertSame(11, $hilbert(11)->inv()->rowCount);

        $this->expectException(ArithmeticException::class);
        $this->expectExceptionMessage('Cannot invert singular Matrix.');
        $hilbert(12)->inv();
    }

    /**
     * Test inv() of a matrix larger than 3×3, inverted by LU decomposition: A × A⁻¹ = I.
     */
    public function testInvLarge(): void
    {
        $m = Matrix::fromArray([[4, 1, 0, 0, 2], [1, 5, 1, 0, 0], [0, 1, 6, 1, 0], [0, 0, 1, 7, 1], [2, 0, 0, 1, 8]]);

        $this->assertTrue($m->mul($m->inv())->approxEqual(Matrix::identity(5), absTol: 1e-12));
    }

    /**
     * Test inv() of a matrix larger than 3×3 with zeros on the diagonal, which needs row swaps.
     */
    public function testInvLargeNeedsPivoting(): void
    {
        // A permutation matrix's inverse is its transpose.
        $m = Matrix::fromArray([[0, 1, 0, 0], [0, 0, 0, 1], [1, 0, 0, 0], [0, 0, 1, 0]]);

        $this->assertTrue($m->inv()->equal($m->t()));
    }

    /**
     * Test inv() of a matrix larger than 3×3 whose rows differ widely in scale.
     */
    public function testInvLargeMixedMagnitude(): void
    {
        $m = Matrix::fromArray([[1e-300, 0, 0, 0], [0, 2, 0, 0], [0, 0, 4, 0], [0, 0, 0, 1e300]]);
        $inv = $m->inv();

        $this->assertApproxEqual(1e300, $inv->get(0, 0));
        $this->assertSame(0.5, $inv->get(1, 1));
        $this->assertSame(0.25, $inv->get(2, 2));
        $this->assertApproxEqual(1e-300, $inv->get(3, 3));
    }

    /**
     * Data provider for testInvHasNoNegativeZeros().
     *
     * @return array<string, array{list<list<int>>}>
     */
    public static function invNegativeZeroProvider(): array
    {
        return [
            '2×2 identity' => [[[1, 0], [0, 1]]],
            '3×3 identity' => [[[1, 0, 0], [0, 1, 0], [0, 0, 1]]],
            '3×3 upper' => [[[1, 2, 0], [0, 1, 0], [0, 0, 1]]],
            '4×4 diagonal (LU)' => [[[2, 0, 0, 0], [0, -4, 0, 0], [0, 0, 1, 0], [0, 0, 0, -1]]],
        ];
    }

    /**
     * Test inv() and pow(-1) give positive zeros, not negative zeros, on both the adjugate and LU paths.
     *
     * @param list<list<int>> $data The matrix to invert.
     */
    #[DataProvider('invNegativeZeroProvider')]
    public function testInvHasNoNegativeZeros(array $data): void
    {
        $m = Matrix::fromArray($data);

        foreach (
            [$m->inv(), $m->pow(-1)] as $inverse
        ) {
            foreach ($inverse->toArray() as $row) {
                foreach ($row as $element) {
                    $this->assertFalse(Floats::isNegativeZero($element));
                }
            }
        }
    }

    #endregion
}
