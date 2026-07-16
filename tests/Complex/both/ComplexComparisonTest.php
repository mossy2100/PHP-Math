<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use OceanMoon\Core\Exceptions\ConversionException;
use OceanMoon\Core\Floats;
use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use TypeError;

#[CoversClass(Complex::class)]
class ComplexComparisonTest extends TestCase
{
    #region Identity tests

    /**
     * Test identical returns true for a Complex with the same real and imaginary parts.
     */
    public function testIdentical(): void
    {
        $z1 = new Complex(3, 4);
        $z2 = new Complex(3, 4);

        $this->assertTrue($z1->identical($z2));
    }

    /**
     * Test identical is reflexive: a value is always identical to itself.
     */
    public function testIdenticalReflexive(): void
    {
        $z = new Complex(3, 4);

        $this->assertTrue($z->identical($z));
    }

    /**
     * Test identical returns false for a Complex with different real and/or imaginary parts.
     */
    public function testIdenticalDifferentValues(): void
    {
        $z1 = new Complex(3, 4);

        $this->assertFalse($z1->identical(new Complex(3, 5)));
        $this->assertFalse($z1->identical(new Complex(4, 4)));
        $this->assertFalse($z1->identical(new Complex(4, 5)));
    }

    /**
     * Test identical returns false for values equal() would accept but that aren't Complex
     * instances -- int, float, string, array, and object.
     */
    public function testIdenticalWithNonComplexReturnsFalse(): void
    {
        $z = new Complex(3, 4);

        $this->assertFalse($z->identical(3));
        $this->assertFalse($z->identical(3.0));
        $this->assertFalse($z->identical('3+4i'));
        $this->assertFalse($z->identical([3, 4]));
        $this->assertFalse($z->identical((object) [
            'real'      => 3,
            'imaginary' => 4,
        ]));
    }

    /**
     * Test identical returns false for other unrelated types.
     */
    public function testIdenticalWithInvalidTypeReturnsFalse(): void
    {
        $z = new Complex(3, 4);

        $this->assertFalse($z->identical(null));
        $this->assertFalse($z->identical([]));
        $this->assertFalse($z->identical(new stdClass()));
        $this->assertFalse($z->identical(true));
    }

    /**
     * Test identical treats -0.0 and 0.0 as identical, matching PHP's own -0.0 === 0.0 behavior.
     */
    public function testIdenticalNegativeZero(): void
    {
        $z1 = new Complex(-0.0, 0);
        $z2 = new Complex(0.0, 0);

        $this->assertTrue($z1->identical($z2));

        $z3 = new Complex(0, -0.0);
        $z4 = new Complex(0, 0.0);

        $this->assertTrue($z3->identical($z4));
    }

    #endregion

    #region Exact equality tests

    /**
     * Test exact equality with identical complex numbers.
     */
    public function testEqualExact(): void
    {
        $z1 = new Complex(3, 4);
        $z2 = new Complex(3, 4);

        $this->assertTrue($z1->equal($z2));
    }

    /**
     * Test inequality with different complex numbers.
     */
    public function testNotEqual(): void
    {
        $z1 = new Complex(3, 4);
        $z2 = new Complex(3, 5);

        $this->assertFalse($z1->equal($z2));

        $z3 = new Complex(4, 4);
        $this->assertFalse($z1->equal($z3));

        $z4 = new Complex(4, 5);
        $this->assertFalse($z1->equal($z4));
    }

    /**
     * Test equality with real numbers (int and float).
     */
    public function testEqualWithRealNumber(): void
    {
        $z = new Complex(5, 0);

        // Should work with both int and float
        $this->assertTrue($z->equal(5));
        $this->assertTrue($z->equal(5.0));
        $this->assertFalse($z->equal(6));
        $this->assertFalse($z->equal(4.99999));
    }

    /**
     * Test equality with zero.
     */
    public function testEqualWithZero(): void
    {
        $z = new Complex(0, 0);

        $this->assertTrue($z->equal(0));
        $this->assertTrue($z->equal(0.0));
        $this->assertTrue($z->equal(new Complex(0, 0)));
        $this->assertFalse($z->equal(new Complex(0, 1e-100)));
    }

    /**
     * Test reflexivity: a value should equal itself.
     */
    public function testEqualReflexive(): void
    {
        $z1 = new Complex(3, 4);
        $this->assertTrue($z1->equal($z1));

        $z2 = new Complex(-5.7, 2.3);
        $this->assertTrue($z2->equal($z2));

        $z3 = new Complex(0, 0);
        $this->assertTrue($z3->equal($z3));
    }

    /**
     * Test symmetry: if a equals b, then b equals a.
     */
    public function testEqualSymmetric(): void
    {
        $z1 = new Complex(3, 4);
        $z2 = new Complex(3, 4);

        $this->assertTrue($z1->equal($z2));
        $this->assertTrue($z2->equal($z1));

        $z3 = new Complex(5, 6);
        $z4 = new Complex(5, 7);

        $this->assertFalse($z3->equal($z4));
        $this->assertFalse($z4->equal($z3));
    }

    /**
     * Test transitivity: if a equals b and b equals c, then a equals c.
     */
    public function testEqualTransitive(): void
    {
        $z1 = new Complex(5, 6);
        $z2 = new Complex(5, 6);
        $z3 = new Complex(5, 6);

        $this->assertTrue($z1->equal($z2));
        $this->assertTrue($z2->equal($z3));
        $this->assertTrue($z1->equal($z3));
    }

    /**
     * Test equality with negative zero.
     */
    public function testEqualNegativeZero(): void
    {
        // In PHP, -0.0 === 0.0 is true, so Complex should treat them as equal
        $z1 = new Complex(-0.0, 0);
        $z2 = new Complex(0.0, 0);

        $this->assertTrue($z1->equal($z2));

        $z3 = new Complex(0, -0.0);
        $z4 = new Complex(0, 0.0);

        $this->assertTrue($z3->equal($z4));

        $z5 = new Complex(-0.0, -0.0);
        $z6 = new Complex(0.0, 0.0);

        $this->assertTrue($z5->equal($z6));
    }

    /**
     * Test equal with a non-parseable string throws ConversionException.
     */
    public function testEqualInvalidStringThrows(): void
    {
        $this->expectException(ConversionException::class);
        $z = new Complex(3, 4);
        $z->equal('string');
    }

    /**
     * Test equal with null throws ConversionException.
     */
    public function testEqualWithNullThrows(): void
    {
        $this->expectException(ConversionException::class);
        $z = new Complex(3, 4);
        $z->equal(null);
    }

    /**
     * Test equal with an empty array throws ConversionException (not exactly two elements).
     */
    public function testEqualWithEmptyArrayThrows(): void
    {
        $this->expectException(ConversionException::class);
        $z = new Complex(3, 4);
        $z->equal([]);
    }

    /**
     * Test equal with an object lacking real/imaginary properties throws ConversionException.
     */
    public function testEqualWithInvalidObjectThrows(): void
    {
        $this->expectException(ConversionException::class);
        $z = new Complex(3, 4);
        $z->equal(new stdClass());
    }

    /**
     * Test equal with a bool throws.
     */
    public function testEqualWithBoolThrows(): void
    {
        $this->expectException(TypeError::class);
        $z = new Complex(3, 4);
        $z->equal(true);
    }

    /**
     * Test equality with a string throws.
     */
    public function testEqualWithStringThrows(): void
    {
        $this->expectException(TypeError::class);
        $z = new Complex(3, 4);
        $z->equal('3+4i');
    }

    /**
     * Test equality with an array throws.
     */
    public function testEqualWithArrayThrows(): void
    {
        $this->expectException(TypeError::class);
        $z = new Complex(3, 4);
        $z->equal([3, 4]);
    }

    /**
     * Test equal with a 3-element array throws ConversionException (not exactly two elements).
     */
    public function testEqualWithWrongSizedArrayThrows(): void
    {
        $this->expectException(ConversionException::class);
        $z = new Complex(3, 4);
        $z->equal([1, 2, 3]);
    }

    /**
     * Test equality with a plain object with numeric real/imaginary properties, converted via
     * toComplex().
     */
    public function testEqualWithObject(): void
    {
        $z = new Complex(3, 4);

        $this->assertTrue($z->equal((object) [
            'real'      => 3,
            'imaginary' => 4,
        ]));
        $this->assertFalse($z->equal((object) [
            'real'      => 3,
            'imaginary' => 5,
        ]));
    }

    /**
     * Test equality with pure imaginary numbers.
     */
    public function testEqualPureImaginary(): void
    {
        $z1 = new Complex(0, 5);
        $z2 = new Complex(0, 5);

        $this->assertTrue($z1->equal($z2));

        $z3 = new Complex(0, -5);
        $this->assertFalse($z1->equal($z3));
    }

    #endregion

    #region Approximate equality tests

    /**
     * Test basic approximate equality with default tolerances.
     */
    public function testApproxEqualBasic(): void
    {
        $z1 = new Complex(3.00000000001, 4.00000000001);
        $z2 = new Complex(3, 4);

        // Should be equal with default tolerance
        $this->assertTrue($z1->approxEqual($z2));
    }

    /**
     * Test approximate equality with tight tolerance.
     */
    public function testApproxEqualTightTolerance(): void
    {
        $z1 = new Complex(3.00000000001, 4.00000000001);
        $z2 = new Complex(3, 4);

        // Should not be equal with very tight tolerance
        $this->assertFalse($z1->approxEqual($z2, 1e-15, 1e-15));
    }

    /**
     * Test approximate equality with zero tolerances (exact match required).
     */
    public function testApproxEqualZeroTolerances(): void
    {
        $z1 = new Complex(3.0, 4.0);
        $z2 = new Complex(3.0 + 1e-15, 4.0);

        // With zero tolerances, should require exact match
        $this->assertFalse($z1->approxEqual($z2, 0.0, 0.0));
        $this->assertTrue($z1->approxEqual($z1, 0.0, 0.0));

        $z3 = new Complex(5.0, 6.0);
        $z4 = new Complex(5.0, 6.0);

        $this->assertTrue($z3->approxEqual($z4, 0.0, 0.0));
    }

    /**
     * Test approximate equality with relative tolerance only.
     */
    public function testApproxEqualRelativeTolerance(): void
    {
        // Large values - relative tolerance matters more
        $z1 = new Complex(1e10, 0);
        $z2 = new Complex(1e10 + 1, 0);

        // Small absolute difference but within relative tolerance
        $this->assertTrue($z1->approxEqual($z2, 1e-9, 0.0));

        // Outside relative tolerance
        $this->assertFalse($z1->approxEqual($z2, 1e-12, 0.0));
    }

    /**
     * Test approximate equality with absolute tolerance only.
     */
    public function testApproxEqualAbsoluteTolerance(): void
    {
        // Near zero - absolute tolerance matters more
        $z1 = new Complex(1e-10, 0);
        $z2 = new Complex(2e-10, 0);

        // Relative tolerance won't help here, needs absolute
        $this->assertTrue($z1->approxEqual($z2, 0.0, 1e-9));

        // Outside absolute tolerance
        $this->assertFalse($z1->approxEqual($z2, 0.0, 1e-11));
    }

    /**
     * Test approximate equality with both components differing.
     */
    public function testApproxEqualBothComponents(): void
    {
        $z1 = new Complex(3.0, 4.0);
        $z2 = new Complex(3.0 + 1e-10, 4.0 + 1e-10);

        // Both components within tolerance
        $this->assertTrue($z1->approxEqual($z2));

        $z3 = new Complex(3.0 + 1e-10, 4.0 + 1e-5);

        // One component outside default tolerance
        $this->assertFalse($z1->approxEqual($z3));
    }

    /**
     * Test approximate equality when only one component is within tolerance.
     */
    public function testApproxEqualOneComponentOutsideTolerance(): void
    {
        $z1 = new Complex(3.0, 4.0);

        // Real part matches, imaginary doesn't
        $z2 = new Complex(3.0, 4.1);
        $this->assertFalse($z1->approxEqual($z2));

        // Imaginary part matches, real doesn't
        $z3 = new Complex(3.1, 4.0);
        $this->assertFalse($z1->approxEqual($z3));
    }

    /**
     * Test approximate equality with real numbers (int and float).
     */
    public function testApproxEqualWithNumbers(): void
    {
        $z = new Complex(5.0, 0);

        // Should work with int and float like equal() does
        $this->assertTrue($z->approxEqual(5));
        $this->assertTrue($z->approxEqual(5.0));
        $this->assertTrue($z->approxEqual(5.0 + 1e-11));

        // Outside default tolerance
        $this->assertFalse($z->approxEqual(5.01));
    }

    /**
     * Test approxEqual with a non-parseable string throws ConversionException.
     */
    public function testApproxEqualInvalidStringThrows(): void
    {
        $this->expectException(ConversionException::class);
        $z = new Complex(3, 4);
        $z->approxEqual('string');
    }

    /**
     * Test approxEqual with null throws ConversionException.
     */
    public function testApproxEqualWithNullThrows(): void
    {
        $this->expectException(ConversionException::class);
        $z = new Complex(3, 4);
        $z->approxEqual(null);
    }

    /**
     * Test approxEqual with an empty array throws ConversionException.
     */
    public function testApproxEqualWithEmptyArrayThrows(): void
    {
        $this->expectException(ConversionException::class);
        $z = new Complex(3, 4);
        $z->approxEqual([]);
    }

    /**
     * Test approxEqual with an object lacking real/imaginary properties throws ConversionException.
     */
    public function testApproxEqualWithInvalidObjectThrows(): void
    {
        $this->expectException(ConversionException::class);
        $z = new Complex(3, 4);
        $z->approxEqual(new stdClass());
    }

    /**
     * Test approxEqual with a bool throws ConversionException.
     */
    public function testApproxEqualWithBoolThrows(): void
    {
        $this->expectException(ConversionException::class);
        $z = new Complex(3, 4);
        $z->approxEqual(true);
    }

    /**
     * Test approximate equality with a parseable string, converted via toComplex().
     */
    public function testApproxEqualWithString(): void
    {
        $z = new Complex(3, 4);

        $this->assertTrue($z->approxEqual('3.0000000001+4.0000000001i'));
        $this->assertFalse($z->approxEqual('3.5+4i'));
    }

    /**
     * Test approxEqual with a non-parseable string throws ConversionException.
     */
    public function testApproxEqualWithUnparseableStringThrows(): void
    {
        $this->expectException(ConversionException::class);
        $z = new Complex(3, 4);
        $z->approxEqual('not a number');
    }

    /**
     * Test approximate equality with a 2-element array (list or associative), converted via
     * toComplex().
     */
    public function testApproxEqualWithArray(): void
    {
        $z = new Complex(3, 4);

        $this->assertTrue($z->approxEqual([3.0000000001, 4.0000000001]));
        $this->assertFalse($z->approxEqual([3.5, 4]));
    }

    /**
     * Test approximate equality with a plain object with numeric real/imaginary properties,
     * converted via toComplex().
     */
    public function testApproxEqualWithObject(): void
    {
        $z = new Complex(3, 4);

        $this->assertTrue($z->approxEqual((object) [
            'real'      => 3.0000000001,
            'imaginary' => 4.0000000001,
        ]));
        $this->assertFalse($z->approxEqual((object) [
            'real'      => 3.5,
            'imaginary' => 4,
        ]));
    }

    /**
     * Test approximate equality with custom tolerances.
     */
    public function testApproxEqualCustomTolerances(): void
    {
        $z1 = new Complex(100.0, 200.0);
        $z2 = new Complex(100.5, 200.5);

        // Within loose tolerance
        $this->assertTrue($z1->approxEqual($z2, 0.01, 1.0));

        // Outside tight tolerance
        $this->assertFalse($z1->approxEqual($z2, 1e-6, 0.1));
    }

    /**
     * Test approximate equality with negative zero.
     */
    public function testApproxEqualNegativeZero(): void
    {
        $z1 = new Complex(-0.0, 0);
        $z2 = new Complex(0.0, 0);

        $this->assertTrue($z1->approxEqual($z2));

        $z3 = new Complex(0, -0.0);
        $z4 = new Complex(0, 0.0);

        $this->assertTrue($z3->approxEqual($z4));
    }

    /**
     * Test approximate equality using default Floats constants.
     */
    public function testApproxEqualDefaultConstants(): void
    {
        $z1 = new Complex(1.0, 2.0);
        $z2 = new Complex(1.0 + Floats::DEFAULT_RELATIVE_TOLERANCE / 10, 2.0);

        // Within default relative tolerance
        $this->assertTrue($z1->approxEqual($z2));

        $z3 = new Complex(1.0, 2.0 + Floats::DEFAULT_RELATIVE_TOLERANCE * 10);

        // Outside default relative tolerance
        $this->assertFalse($z1->approxEqual($z3));
    }

    /**
     * Test approximate equality with very small values.
     */
    public function testApproxEqualVerySmallValues(): void
    {
        $z1 = new Complex(1e-20, 1e-20);
        $z2 = new Complex(2e-20, 2e-20);

        // Within absolute tolerance (PHP_FLOAT_EPSILON)
        $this->assertTrue($z1->approxEqual($z2));

        // Outside tight absolute tolerance
        $this->assertFalse($z1->approxEqual($z2, 0.0, 1e-30));
    }

    /**
     * Test approximate equality with very large values.
     */
    public function testApproxEqualVeryLargeValues(): void
    {
        $z1 = new Complex(1e15, 2e15);
        $z2 = new Complex(1e15 + 1e6, 2e15 + 1e6);

        // Within relative tolerance (1e-9 of 1e15 = 1e6)
        $this->assertTrue($z1->approxEqual($z2));

        // Outside tight relative tolerance
        $this->assertFalse($z1->approxEqual($z2, 1e-12, 0.0));
    }

    /**
     * Test approximate equality is reflexive.
     */
    public function testApproxEqualReflexive(): void
    {
        $z = new Complex(3.14159, 2.71828);

        $this->assertTrue($z->approxEqual($z));
        $this->assertTrue($z->approxEqual($z, 0.0, 0.0));
    }

    /**
     * Test approximate equality is symmetric.
     */
    public function testApproxEqualSymmetric(): void
    {
        $z1 = new Complex(3.0, 4.0);
        $z2 = new Complex(3.0 + 1e-10, 4.0 + 1e-10);

        $this->assertTrue($z1->approxEqual($z2));
        $this->assertTrue($z2->approxEqual($z1));
    }

    #endregion
}
