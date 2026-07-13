<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use OceanMoon\Core\Exceptions\ConversionException;
use OceanMoon\Math\Matrix;
use OceanMoon\Math\Vector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Matrix::class)]
class MatrixComparisonTest extends TestCase
{
    #region Identical tests

    /**
     * Test identical returns true for a Matrix with the same dimensions and elements.
     */
    public function testIdentical(): void
    {
        $a = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $b = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);

        $this->assertTrue($a->identical($b));
    }

    /**
     * Test identical is reflexive: a value is always identical to itself.
     */
    public function testIdenticalReflexive(): void
    {
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);

        $this->assertTrue($m->identical($m));
    }

    /**
     * Test identical returns false for a Matrix with different elements.
     */
    public function testIdenticalDifferentValues(): void
    {
        $a = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $b = Matrix::fromArray([
            [1, 2],
            [3, 5],
        ]);

        $this->assertFalse($a->identical($b));
    }

    /**
     * Test identical returns false for values equal() would accept but that aren't Matrix instances --
     * a flat list, a rectangular array, and a Vector -- even when the values match.
     */
    public function testIdenticalWithNonMatrixReturnsFalse(): void
    {
        $m = Matrix::fromArray([
            [1],
            [2],
            [3],
        ]);

        $this->assertFalse($m->identical([1, 2, 3]));
        $this->assertFalse($m->identical([
            [1],
            [2],
            [3],
        ]));
        $this->assertFalse($m->identical(Vector::fromArray([1, 2, 3])));
    }

    /**
     * Test identical returns false for other unrelated types, without throwing.
     */
    public function testIdenticalWithInvalidTypeReturnsFalse(): void
    {
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);

        $this->assertFalse($m->identical('not a matrix'));
        $this->assertFalse($m->identical(null));
        $this->assertFalse($m->identical(42));
        $this->assertFalse($m->identical(new stdClass()));
    }

    #endregion

    #region Equal tests

    /**
     * Test equal with identical matrices.
     */
    public function testEqualWithIdenticalMatrices(): void
    {
        $a = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $b = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $this->assertTrue($a->equal($b));
    }

    /**
     * Test equal with different values but same dimensions.
     */
    public function testEqualWithDifferentValues(): void
    {
        $a = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $b = Matrix::fromArray([
            [1, 2],
            [3, 5],
        ]);
        $this->assertFalse($a->equal($b));
    }

    /**
     * Test equal with different dimensions.
     */
    public function testEqualWithDifferentDimensions(): void
    {
        $a = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $b = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $this->assertFalse($a->equal($b));
    }

    /**
     * Test equal with a flat list of numbers, converted via toMatrix() to a single column.
     */
    public function testEqualWithFlatArrayOfNumbers(): void
    {
        $m = Matrix::fromArray([
            [1],
            [2],
            [3],
        ]);

        $this->assertTrue($m->equal([1, 2, 3]));
        $this->assertFalse($m->equal([1, 2, 4]));
    }

    /**
     * Test equal with a rectangular array of numbers, converted via toMatrix().
     */
    public function testEqualWithRectangularArray(): void
    {
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);

        $this->assertTrue($m->equal([
            [1, 2],
            [3, 4],
        ]));
        $this->assertFalse($m->equal([
            [1, 2],
            [3, 5],
        ]));
    }

    /**
     * Test equal with a Vector, treated as a column matrix by default, converted via toMatrix().
     */
    public function testEqualWithVector(): void
    {
        $m = Matrix::fromArray([
            [1],
            [2],
            [3],
        ]);

        $this->assertTrue($m->equal(Vector::fromArray([1, 2, 3])));
        $this->assertFalse($m->equal(Vector::fromArray([1, 2, 4])));
    }

    /**
     * Test equal with a ragged (non-rectangular) array throws ConversionException.
     */
    public function testEqualWithRaggedArrayThrows(): void
    {
        $this->expectException(ConversionException::class);
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $m->equal([
            [1, 2],
            [3, 4, 5],
        ]);
    }

    /**
     * Test equal with an array containing non-numeric elements throws ConversionException.
     */
    public function testEqualWithNonNumericArrayThrows(): void
    {
        $this->expectException(ConversionException::class);
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $m->equal([
            ['a', 'b'],
            ['c', 'd'],
        ]);
    }

    /**
     * Test equal with a string throws ConversionException.
     */
    public function testEqualWithStringThrows(): void
    {
        $this->expectException(ConversionException::class);
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $m->equal('not a matrix');
    }

    /**
     * Test equal with null throws ConversionException.
     */
    public function testEqualWithNullThrows(): void
    {
        $this->expectException(ConversionException::class);
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $m->equal(null);
    }

    /**
     * Test equal with an int throws ConversionException.
     */
    public function testEqualWithIntThrows(): void
    {
        $this->expectException(ConversionException::class);
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $m->equal(42);
    }

    /**
     * Test equal with empty matrices.
     */
    public function testEqualWithEmptyMatrices(): void
    {
        $a = new Matrix(0, 0);
        $b = new Matrix(0, 0);
        $this->assertTrue($a->equal($b));
    }

    #endregion

    #region Approximate equality tests

    /**
     * Test approxEqual with close values within default tolerance.
     */
    public function testApproxEqualWithCloseValues(): void
    {
        $a = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);
        $b = Matrix::fromArray([
            [1.0 + 1e-12, 2.0 - 1e-12],
            [3.0 + 1e-12, 4.0 - 1e-12],
        ]);
        $this->assertTrue($a->approxEqual($b));
    }

    /**
     * Test approxEqual with values outside tolerance returns false.
     */
    public function testApproxEqualWithValuesOutsideTolerance(): void
    {
        $a = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);
        $b = Matrix::fromArray([
            [1.1, 2.0],
            [3.0, 4.0],
        ]);
        $this->assertFalse($a->approxEqual($b));
    }

    /**
     * Test approxEqual with different dimensions returns false.
     */
    public function testApproxEqualWithDifferentDimensions(): void
    {
        $a = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);
        $b = Matrix::fromArray([
            [1.0, 2.0, 3.0],
        ]);
        $this->assertFalse($a->approxEqual($b));
    }

    /**
     * Test approxEqual with a flat list of numbers, converted via toMatrix() to a single column.
     */
    public function testApproxEqualWithFlatArrayOfNumbers(): void
    {
        $m = Matrix::fromArray([
            [1.0],
            [2.0],
            [3.0],
        ]);

        $this->assertTrue($m->approxEqual([1.0 + 1e-12, 2.0, 3.0]));
        $this->assertFalse($m->approxEqual([1.1, 2.0, 3.0]));
    }

    /**
     * Test approxEqual with a rectangular array of numbers, converted via toMatrix().
     */
    public function testApproxEqualWithRectangularArray(): void
    {
        $m = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);

        $this->assertTrue($m->approxEqual([
            [1.0 + 1e-12, 2.0],
            [3.0, 4.0],
        ]));
        $this->assertFalse($m->approxEqual([
            [1.1, 2.0],
            [3.0, 4.0],
        ]));
    }

    /**
     * Test approxEqual with a Vector, treated as a column matrix by default, converted via toMatrix().
     */
    public function testApproxEqualWithVector(): void
    {
        $m = Matrix::fromArray([
            [1.0],
            [2.0],
            [3.0],
        ]);

        $this->assertTrue($m->approxEqual(Vector::fromArray([1.0 + 1e-12, 2.0, 3.0])));
        $this->assertFalse($m->approxEqual(Vector::fromArray([1.1, 2.0, 3.0])));
    }

    /**
     * Test approxEqual with a string throws ConversionException.
     */
    public function testApproxEqualWithStringThrows(): void
    {
        $this->expectException(ConversionException::class);
        $m = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);
        $m->approxEqual('not a matrix');
    }

    /**
     * Test approxEqual with an int throws ConversionException.
     */
    public function testApproxEqualWithIntThrows(): void
    {
        $this->expectException(ConversionException::class);
        $m = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);
        $m->approxEqual(42);
    }

    /**
     * Test approxEqual with null throws ConversionException.
     */
    public function testApproxEqualWithNullThrows(): void
    {
        $this->expectException(ConversionException::class);
        $m = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);
        $m->approxEqual(null);
    }

    /**
     * Test approxEqual with custom tight tolerance.
     */
    public function testApproxEqualWithCustomTightTolerance(): void
    {
        $a = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);

        // With an extremely tight tolerance, even tiny differences should fail.
        $close = Matrix::fromArray([
            [1.0 + 1e-8, 2.0],
            [3.0, 4.0],
        ]);
        $this->assertFalse($a->approxEqual($close, relTol: 1e-15, absTol: 1e-15));

        // But values that are actually equal should still pass.
        $same = Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]);
        $this->assertTrue($a->approxEqual($same, relTol: 1e-15, absTol: 1e-15));
    }

    /**
     * Test approxEqual with empty matrices.
     */
    public function testApproxEqualWithEmptyMatrices(): void
    {
        $a = new Matrix(0, 0);
        $b = new Matrix(0, 0);
        $this->assertTrue($a->approxEqual($b));
    }

    #endregion
}
