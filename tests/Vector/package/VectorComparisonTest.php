<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Vector;

use OceanMoon\Core\Exceptions\ConversionException;
use OceanMoon\Math\Matrix;
use OceanMoon\Math\Vector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Vector::class)]
class VectorComparisonTest extends TestCase
{
    #region Identical tests

    /**
     * Test identical returns true for a Vector with the same elements.
     */
    public function testIdentical(): void
    {
        $a = Vector::fromArray([1, 2, 3]);
        $b = Vector::fromArray([1, 2, 3]);

        $this->assertTrue($a->identical($b));
    }

    /**
     * Test identical is reflexive: a value is always identical to itself.
     */
    public function testIdenticalReflexive(): void
    {
        $v = Vector::fromArray([1, 2, 3]);

        $this->assertTrue($v->identical($v));
    }

    /**
     * Test identical returns false for a Vector with different elements.
     */
    public function testIdenticalDifferentValues(): void
    {
        $a = Vector::fromArray([1, 2, 3]);
        $b = Vector::fromArray([1, 2, 4]);

        $this->assertFalse($a->identical($b));
    }

    /**
     * Test identical returns false for values equal() would accept but that aren't Vector instances --
     * a list of numbers and a single-column Matrix -- even when the values match.
     */
    public function testIdenticalWithNonVectorReturnsFalse(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $col = new Matrix(3, 1);
        $col->setColumn(0, [1, 2, 3]);

        $this->assertFalse($v->identical([1, 2, 3]));
        $this->assertFalse($v->identical($col));
    }

    /**
     * Test identical returns false for other unrelated types, without throwing.
     */
    public function testIdenticalWithInvalidTypeReturnsFalse(): void
    {
        $v = Vector::fromArray([1, 2, 3]);

        $this->assertFalse($v->identical('not a vector'));
        $this->assertFalse($v->identical(null));
        $this->assertFalse($v->identical(42));
        $this->assertFalse($v->identical(new stdClass()));
    }

    #endregion

    #region Equal tests

    /**
     * Test equal with identical vectors.
     */
    public function testEqualWithIdenticalVectors(): void
    {
        $a = Vector::fromArray([1, 2, 3]);
        $b = Vector::fromArray([1, 2, 3]);
        $this->assertTrue($a->equal($b));
    }

    /**
     * Test equal with different values.
     */
    public function testEqualWithDifferentValues(): void
    {
        $a = Vector::fromArray([1, 2, 3]);
        $b = Vector::fromArray([1, 2, 4]);
        $this->assertFalse($a->equal($b));
    }

    /**
     * Test equal with different sizes.
     */
    public function testEqualWithDifferentSizes(): void
    {
        $a = Vector::fromArray([1, 2, 3]);
        $b = Vector::fromArray([1, 2]);
        $this->assertFalse($a->equal($b));
    }

    /**
     * Test equal with a list of numbers, converted via toVector().
     */
    public function testEqualWithArrayOfNumbers(): void
    {
        $v = Vector::fromArray([1, 2, 3]);

        $this->assertTrue($v->equal([1, 2, 3]));
        $this->assertFalse($v->equal([1, 2, 4]));
    }

    /**
     * Test equal with a single-row Matrix throws ConversionException. Only single-column matrices
     * are convertible to a Vector, matching the column-vector convention used elsewhere; a row
     * matrix is not.
     */
    public function testEqualWithSingleRowMatrixThrows(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $row = new Matrix(1, 3);
        $row->setRow(0, [1, 2, 3]);

        $this->expectException(ConversionException::class);
        $v->equal($row);
    }

    /**
     * Test equal with a single-column Matrix, converted via toVector().
     */
    public function testEqualWithSingleColumnMatrix(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $col = new Matrix(3, 1);
        $col->setColumn(0, [1, 2, 3]);

        $this->assertTrue($v->equal($col));
    }

    /**
     * Test equal with a Matrix that has neither one row nor one column throws ConversionException.
     */
    public function testEqualWithMultiRowColumnMatrixThrows(): void
    {
        $this->expectException(ConversionException::class);
        $v = Vector::fromArray([1, 2, 3, 4]);
        $v->equal(Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]));
    }

    /**
     * Test equal with an array containing non-numeric elements throws ConversionException.
     */
    public function testEqualWithNonNumericArrayThrows(): void
    {
        $this->expectException(ConversionException::class);
        $v = Vector::fromArray([1, 2, 3]);
        $v->equal(['a', 'b', 'c']);
    }

    /**
     * Test equal with a string throws ConversionException.
     */
    public function testEqualWithStringThrows(): void
    {
        $this->expectException(ConversionException::class);
        $v = Vector::fromArray([1, 2, 3]);
        $v->equal('not a vector');
    }

    /**
     * Test equal with an int throws ConversionException.
     */
    public function testEqualWithIntThrows(): void
    {
        $this->expectException(ConversionException::class);
        $v = Vector::fromArray([1, 2, 3]);
        $v->equal(42);
    }

    /**
     * Test equal with null throws ConversionException.
     */
    public function testEqualWithNullThrows(): void
    {
        $this->expectException(ConversionException::class);
        $v = Vector::fromArray([1, 2, 3]);
        $v->equal(null);
    }

    #endregion

    #region Approximate equality tests

    /**
     * Test approxEqual with close values.
     */
    public function testApproxEqualWithCloseValues(): void
    {
        $a = Vector::fromArray([1.0, 2.0, 3.0]);
        $b = Vector::fromArray([1.0 + 1e-12, 2.0 - 1e-12, 3.0 + 1e-12]);
        $this->assertTrue($a->approxEqual($b));
    }

    /**
     * Test approxEqual with values outside tolerance.
     */
    public function testApproxEqualWithValuesOutsideTolerance(): void
    {
        $a = Vector::fromArray([1.0, 2.0, 3.0]);
        $b = Vector::fromArray([1.1, 2.0, 3.0]);
        $this->assertFalse($a->approxEqual($b));
    }

    /**
     * Test approxEqual with different sizes.
     */
    public function testApproxEqualWithDifferentSizes(): void
    {
        $a = Vector::fromArray([1.0, 2.0, 3.0]);
        $b = Vector::fromArray([1.0, 2.0]);
        $this->assertFalse($a->approxEqual($b));
    }

    /**
     * Test approxEqual with a list of numbers, converted via toVector().
     */
    public function testApproxEqualWithArrayOfNumbers(): void
    {
        $v = Vector::fromArray([1.0, 2.0, 3.0]);

        $this->assertTrue($v->approxEqual([1.0 + 1e-12, 2.0, 3.0]));
        $this->assertFalse($v->approxEqual([1.1, 2.0, 3.0]));
    }

    /**
     * Test approxEqual with a single-column Matrix, converted via toVector().
     */
    public function testApproxEqualWithSingleColumnMatrix(): void
    {
        $v = Vector::fromArray([1.0, 2.0, 3.0]);
        $col = new Matrix(3, 1);
        $col->setColumn(0, [1.0 + 1e-12, 2.0, 3.0]);

        $this->assertTrue($v->approxEqual($col));
    }

    /**
     * Test approxEqual with a Matrix that has neither one row nor one column throws
     * ConversionException.
     */
    public function testApproxEqualWithMultiRowColumnMatrixThrows(): void
    {
        $this->expectException(ConversionException::class);
        $v = Vector::fromArray([1.0, 2.0, 3.0, 4.0]);
        $v->approxEqual(Matrix::fromArray([
            [1.0, 2.0],
            [3.0, 4.0],
        ]));
    }

    /**
     * Test approxEqual with a string throws ConversionException.
     */
    public function testApproxEqualWithStringThrows(): void
    {
        $this->expectException(ConversionException::class);
        $v = Vector::fromArray([1.0, 2.0, 3.0]);
        $v->approxEqual('not a vector');
    }

    /**
     * Test approxEqual with an int throws ConversionException.
     */
    public function testApproxEqualWithIntThrows(): void
    {
        $this->expectException(ConversionException::class);
        $v = Vector::fromArray([1.0, 2.0, 3.0]);
        $v->approxEqual(42);
    }

    /**
     * Test approxEqual with null throws ConversionException.
     */
    public function testApproxEqualWithNullThrows(): void
    {
        $this->expectException(ConversionException::class);
        $v = Vector::fromArray([1.0, 2.0, 3.0]);
        $v->approxEqual(null);
    }

    #endregion
}
