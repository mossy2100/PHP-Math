<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Rational;

use DomainException;
use InvalidArgumentException;
use OceanMoon\Math\Rational;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Rational::class)]
class RationalComparisonTest extends TestCase
{
    #region Method compare() tests.

    /**
     * Test compare with equal Rationals.
     */
    public function testCompareEqual(): void
    {
        $r1 = new Rational(3, 4);
        $r2 = new Rational(3, 4);

        $this->assertSame(0, $r1->compare($r2));

        // Different representations of same value
        $r3 = new Rational(6, 8);
        $this->assertSame(0, $r1->compare($r3));
    }

    /**
     * Test compare with less than.
     */
    public function testCompareLessThan(): void
    {
        $r1 = new Rational(1, 3);
        $r2 = new Rational(1, 2);

        $this->assertSame(-1, $r1->compare($r2));
    }

    /**
     * Test compare with greater than.
     */
    public function testCompareGreaterThan(): void
    {
        $r1 = new Rational(3, 4);
        $r2 = new Rational(1, 2);

        $this->assertSame(1, $r1->compare($r2));
    }

    /**
     * Test compare with integer.
     */
    public function testCompareWithInteger(): void
    {
        $r = new Rational(3, 2);

        $this->assertSame(-1, $r->compare(2)); // 3/2 < 2
        $this->assertSame(1, $r->compare(1));  // 3/2 > 1
        $this->assertSame(0, new Rational(4, 2)->compare(2)); // 2 == 2
    }

    /**
     * Test compare with float.
     */
    public function testCompareWithFloat(): void
    {
        $r = new Rational(1, 2);

        $this->assertSame(0, $r->compare(0.5));  // 1/2 == 0.5
        $this->assertSame(-1, $r->compare(0.6)); // 1/2 < 0.6
        $this->assertSame(1, $r->compare(0.4));  // 1/2 > 0.4
    }

    /**
     * Test compare with floats that could be integers.
     */
    public function testCompareWithFloatsThatCouldBeInts(): void
    {
        $r = new Rational(11, 2);

        $this->assertSame(-1, $r->compare(6.0)); // 11/2 < 6.0
        $this->assertSame(1, $r->compare(5.0));  // 11/2 > 5.0
    }

    /**
     * Test compare with same denominator optimization.
     */
    public function testCompareSameDenominator(): void
    {
        $r1 = new Rational(3, 7);
        $r2 = new Rational(5, 7);

        $this->assertSame(-1, $r1->compare($r2));
        $this->assertSame(1, $r2->compare($r1));
    }

    /**
     * Test compare with negative numbers.
     */
    public function testCompareNegative(): void
    {
        $r1 = new Rational(-3, 4);
        $r2 = new Rational(1, 4);

        $this->assertSame(-1, $r1->compare($r2));
        $this->assertSame(1, $r2->compare($r1));

        $r3 = new Rational(-1, 2);
        $r4 = new Rational(-3, 4);

        $this->assertSame(1, $r3->compare($r4)); // -1/2 > -3/4
    }

    /**
     * Test compare with large integers that overflow when multiplied.
     */
    public function testCompareWithIntegerMultiplyOverflow(): void
    {
        $r1 = new Rational(2 ** 30 - 1, 2 ** 35);
        $r2 = new Rational(2 ** 30 - 1, 2 ** 33);
        $result = $r1->compare($r2);
        $this->assertSame(-1, $result);
    }

    /**
     * Test compare with invalid type throws InvalidArgumentException.
     */
    public function testCompareInvalidTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $r = new Rational(3, 4);
        $r->compare('string');
    }

    /**
     * Test compare with NAN throws DomainException (NAN has no meaningful ordering).
     */
    public function testCompareWithNanThrows(): void
    {
        $this->expectException(DomainException::class);
        $r = new Rational(3, 4);
        $r->compare(NAN);
    }

    /**
     * Test compare with ±INF. A Rational is always less than INF and greater than -INF, since ±INF, unlike NAN,
     * has a well-defined position in the ordering.
     */
    public function testCompareWithInfinity(): void
    {
        $r = new Rational(3, 4);

        $this->assertSame(-1, $r->compare(INF));
        $this->assertSame(1, $r->compare(-INF));
    }

    /**
     * Test compare with PHP_INT_MIN falls through to float comparison.
     */
    public function testCompareWithPhpIntMin(): void
    {
        $r = new Rational(1, 2);

        // Should compare via floats since PHP_INT_MIN can't be converted to Rational
        $this->assertSame(1, $r->compare(PHP_INT_MIN)); // 0.5 > PHP_INT_MIN

        // Also test with negative rational
        $r2 = new Rational(-1, 2);
        $this->assertSame(1, $r2->compare(PHP_INT_MIN)); // -0.5 > PHP_INT_MIN
    }

    /**
     * Test compare with PHP_INT_MIN as float falls through to float comparison.
     */
    public function testCompareWithPhpIntMinAsFloat(): void
    {
        $r = new Rational(1, 2);

        // (float)PHP_INT_MIN is exactly representable, but should still use float comparison path
        $this->assertSame(1, $r->compare((float) PHP_INT_MIN)); // 0.5 > PHP_INT_MIN
    }

    /**
     * Test compare is reflexive: comparing a value with itself returns 0.
     */
    public function testCompareReflexive(): void
    {
        $r = new Rational(5, 7);
        $this->assertSame(0, $r->compare($r));
    }

    /**
     * Test compare is antisymmetric: if a < b then b > a.
     */
    public function testCompareAntisymmetric(): void
    {
        $r1 = new Rational(1, 3);
        $r2 = new Rational(1, 2);

        $this->assertSame(-1, $r1->compare($r2));
        $this->assertSame(1, $r2->compare($r1));
    }

    /**
     * Test compare is transitive: if a < b and b < c, then a < c.
     */
    public function testCompareTransitive(): void
    {
        $r1 = new Rational(1, 4);
        $r2 = new Rational(1, 3);
        $r3 = new Rational(1, 2);

        $this->assertSame(-1, $r1->compare($r2));
        $this->assertSame(-1, $r2->compare($r3));
        $this->assertSame(-1, $r1->compare($r3));
    }

    #endregion

    #region Method equal() tests.

    /**
     * Test equals with equal Rationals.
     */
    public function testEqualTrue(): void
    {
        $r1 = new Rational(3, 4);
        $r2 = new Rational(3, 4);

        $this->assertTrue($r1->equal($r2));

        // Different representations of same value
        $r3 = new Rational(6, 8);
        $this->assertTrue($r1->equal($r3));
    }

    /**
     * Test equals with unequal Rationals.
     */
    public function testEqualFalse(): void
    {
        $r1 = new Rational(3, 4);
        $r2 = new Rational(1, 2);

        $this->assertFalse($r1->equal($r2));
    }

    /**
     * Test equals with integer.
     */
    public function testEqualWithInteger(): void
    {
        $r = new Rational(4, 2);
        $this->assertTrue($r->equal(2));
        $this->assertFalse($r->equal(3));
    }

    /**
     * Test equals with float.
     */
    public function testEqualWithFloat(): void
    {
        $r = new Rational(1, 2);
        $this->assertTrue($r->equal(0.5));
        $this->assertFalse($r->equal(0.6));
    }

    /**
     * Test equals with invalid type throws exception.
     */
    public function testEqualWithInvalidTypeThrows(): void
    {
        $r = new Rational(3, 4);
        $this->expectException(InvalidArgumentException::class);
        $this->assertFalse($r->equal('string'));
    }

    /**
     * Test equal with NAN throws DomainException (NAN has no meaningful equality result, unlike ±INF).
     */
    public function testEqualWithNanThrows(): void
    {
        $this->expectException(DomainException::class);
        $r = new Rational(3, 4);
        $r->equal(NAN);
    }

    /**
     * Test equal with ±INF returns false, since a Rational (always finite) is never equal to infinity.
     */
    public function testEqualWithInfinity(): void
    {
        $r = new Rational(3, 4);

        $this->assertFalse($r->equal(INF));
        $this->assertFalse($r->equal(-INF));
    }

    /**
     * Test reflexivity: a value should equal itself.
     */
    public function testEqualReflexive(): void
    {
        $r1 = new Rational(3, 4);
        $this->assertTrue($r1->equal($r1));

        $r2 = new Rational(-5, 7);
        $this->assertTrue($r2->equal($r2));

        $r3 = new Rational(0, 1);
        $this->assertTrue($r3->equal($r3));
    }

    /**
     * Test symmetry: if a equals b, then b equals a.
     */
    public function testEqualSymmetric(): void
    {
        $r1 = new Rational(3, 4);
        $r2 = new Rational(6, 8);

        $this->assertTrue($r1->equal($r2));
        $this->assertTrue($r2->equal($r1));

        $r3 = new Rational(1, 2);
        $r4 = new Rational(1, 3);

        $this->assertFalse($r3->equal($r4));
        $this->assertFalse($r4->equal($r3));
    }

    /**
     * Test transitivity: if a equals b and b equals c, then a equals c.
     */
    public function testEqualTransitive(): void
    {
        $r1 = new Rational(2, 4);
        $r2 = new Rational(3, 6);
        $r3 = new Rational(4, 8);

        $this->assertTrue($r1->equal($r2));
        $this->assertTrue($r2->equal($r3));
        $this->assertTrue($r1->equal($r3));
    }

    #endregion

    #region Method lessThan() tests.

    /**
     * Test lessThan.
     */
    public function testLessThan(): void
    {
        $r1 = new Rational(1, 3);
        $r2 = new Rational(1, 2);

        $this->assertTrue($r1->lessThan($r2));
        $this->assertFalse($r2->lessThan($r1));
        $this->assertFalse($r1->lessThan($r1));
    }

    /**
     * Test lessThan with invalid type throws InvalidArgumentException.
     */
    public function testLessThanInvalidTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $r = new Rational(3, 4);
        $r->lessThan('string');
    }

    /**
     * Test lessThan with Rational.
     */
    public function testLessThanWithRational(): void
    {
        $r1 = new Rational(1, 2);
        $r2 = new Rational(3, 4);

        $this->assertTrue($r1->lessThan($r2));
        $this->assertFalse($r2->lessThan($r1));
    }

    #endregion

    #region Method lessThanOrEqual() tests.

    /**
     * Test lessThanOrEqual.
     */
    public function testLessThanOrEqual(): void
    {
        $r1 = new Rational(1, 3);
        $r2 = new Rational(1, 2);
        $r3 = new Rational(1, 3);

        $this->assertTrue($r1->lessThanOrEqual($r2));
        $this->assertTrue($r1->lessThanOrEqual($r3));
        $this->assertFalse($r2->lessThanOrEqual($r1));
    }

    /**
     * Test lessThanOrEqual with invalid type throws InvalidArgumentException.
     */
    public function testLessThanOrEqualInvalidTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $r = new Rational(3, 4);
        $r->lessThanOrEqual([]);
    }

    /**
     * Test lessThanOrEqual boundary cases.
     */
    public function testLessThanOrEqualBoundary(): void
    {
        $r1 = new Rational(1, 2);
        $r2 = new Rational(1, 2);

        // Equal values
        $this->assertTrue($r1->lessThanOrEqual($r2));
        $this->assertTrue($r2->lessThanOrEqual($r1));

        // Less than
        $r3 = new Rational(1, 3);
        $this->assertTrue($r3->lessThanOrEqual($r1));
        $this->assertFalse($r1->lessThanOrEqual($r3));
    }

    #endregion

    #region Method greaterThan() tests.

    /**
     * Test greaterThan.
     */
    public function testGreaterThan(): void
    {
        $r1 = new Rational(3, 4);
        $r2 = new Rational(1, 2);

        $this->assertTrue($r1->greaterThan($r2));
        $this->assertFalse($r2->greaterThan($r1));
        $this->assertFalse($r1->greaterThan($r1));
    }

    /**
     * Test greaterThan with invalid type throws InvalidArgumentException.
     */
    public function testGreaterThanInvalidTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $r = new Rational(3, 4);
        $r->greaterThan(new stdClass());
    }

    /**
     * Test greaterThan with Rational.
     */
    public function testGreaterThanWithRational(): void
    {
        $r1 = new Rational(3, 4);
        $r2 = new Rational(1, 2);

        $this->assertTrue($r1->greaterThan($r2));
        $this->assertFalse($r2->greaterThan($r1));
    }

    #endregion

    #region Method greaterThanOrEqual() tests.

    /**
     * Test greaterThanOrEqual.
     */
    public function testGreaterThanOrEqual(): void
    {
        $r1 = new Rational(3, 4);
        $r2 = new Rational(1, 2);
        $r3 = new Rational(3, 4);

        $this->assertTrue($r1->greaterThanOrEqual($r2));
        $this->assertTrue($r1->greaterThanOrEqual($r3));
        $this->assertFalse($r2->greaterThanOrEqual($r1));
    }

    /**
     * Test greaterThanOrEqual with invalid type throws InvalidArgumentException.
     */
    public function testGreaterThanOrEqualInvalidTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $r = new Rational(3, 4);
        $r->greaterThanOrEqual(null);
    }

    /**
     * Test greaterThanOrEqual boundary cases.
     */
    public function testGreaterThanOrEqualBoundary(): void
    {
        $r1 = new Rational(3, 4);
        $r2 = new Rational(3, 4);

        // Equal values
        $this->assertTrue($r1->greaterThanOrEqual($r2));
        $this->assertTrue($r2->greaterThanOrEqual($r1));

        // Greater than
        $r3 = new Rational(7, 8);
        $this->assertTrue($r3->greaterThanOrEqual($r1));
        $this->assertFalse($r1->greaterThanOrEqual($r3));
    }

    #endregion

    #region Method approxEqual() tests.

    /**
     * Test basic approximate equality with default tolerances.
     */
    public function testApproxEqualBasic(): void
    {
        $r1 = new Rational(1, 3);
        $r2 = 0.333333333333;

        // Should be approximately equal
        $this->assertTrue($r1->approxEqual($r2));
    }

    /**
     * Test approximate equality with tight tolerance.
     */
    public function testApproxEqualTightTolerance(): void
    {
        $r1 = new Rational(1, 3);
        $r2 = 0.33333;

        // Should not be equal with very tight tolerance
        $this->assertFalse($r1->approxEqual($r2, 1e-12, 1e-12));
    }

    /**
     * Test approximate equality with zero tolerances (exact match required).
     */
    public function testApproxEqualZeroTolerances(): void
    {
        $r1 = new Rational(1, 2);
        $r2 = 0.5;

        // Exact match
        $this->assertTrue($r1->approxEqual($r2, 0.0, 0.0));

        $r3 = 0.5 + 1e-15;
        $this->assertFalse($r1->approxEqual($r3, 0.0, 0.0));
    }

    /**
     * Test approximate equality with relative tolerance only.
     */
    public function testApproxEqualRelativeTolerance(): void
    {
        $r = new Rational(1000000, 1);
        $f = 1000001.0;

        // Within relative tolerance
        $this->assertTrue($r->approxEqual($f, 1e-5, 0.0));

        // Outside relative tolerance
        $this->assertFalse($r->approxEqual($f, 1e-8, 0.0));
    }

    /**
     * Test approximate equality with absolute tolerance only.
     */
    public function testApproxEqualAbsoluteTolerance(): void
    {
        $r = new Rational(1, 1000000);
        $f = 2.0 / 1000000;

        // Within absolute tolerance
        $this->assertTrue($r->approxEqual($f, 0.0, 1e-5));

        // Outside absolute tolerance
        $this->assertFalse($r->approxEqual($f, 0.0, 1e-8));
    }

    /**
     * Test approximate equality with Rational.
     */
    public function testApproxEqualWithRational(): void
    {
        $r1 = new Rational(1, 3);
        $r2 = new Rational(333333, 1000000);

        // Close approximation with looser tolerance
        $this->assertTrue($r1->approxEqual($r2, 1e-5, 1e-5));
    }

    /**
     * Test approximate equality with int.
     */
    public function testApproxEqualWithInt(): void
    {
        $r = new Rational(4, 2);

        $this->assertTrue($r->approxEqual(2));
        $this->assertFalse($r->approxEqual(3));
    }

    /**
     * Test approxEqual with NAN throws DomainException (NAN has no meaningful equality result, unlike ±INF).
     */
    public function testApproxEqualWithNanThrows(): void
    {
        $this->expectException(DomainException::class);
        $r = new Rational(3, 4);
        $r->approxEqual(NAN);
    }

    /**
     * Test approxEqual with ±INF returns false, since a Rational (always finite) is never approximately equal to
     * infinity.
     */
    public function testApproxEqualWithInfinity(): void
    {
        $r = new Rational(3, 4);

        $this->assertFalse($r->approxEqual(INF));
        $this->assertFalse($r->approxEqual(-INF));
    }

    /**
     * Test approxEqual with a non-numeric string throws InvalidArgumentException.
     */
    public function testApproxEqualInvalidStringThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $r = new Rational(3, 4);
        $r->approxEqual('string');
    }

    /**
     * Test approxEqual with an array throws InvalidArgumentException.
     */
    public function testApproxEqualWithArrayThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $r = new Rational(3, 4);
        $r->approxEqual([]);
    }

    /**
     * Test approxEqual with an object throws InvalidArgumentException.
     */
    public function testApproxEqualWithObjectThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $r = new Rational(3, 4);
        $r->approxEqual(new stdClass());
    }

    /**
     * Test approxEqual with null throws InvalidArgumentException.
     */
    public function testApproxEqualWithNullThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $r = new Rational(3, 4);
        $r->approxEqual(null);
    }

    /**
     * Test approximate equality is reflexive.
     */
    public function testApproxEqualReflexive(): void
    {
        $r = new Rational(7, 11);

        $this->assertTrue($r->approxEqual($r));
        $this->assertTrue($r->approxEqual($r, 0.0, 0.0));
    }

    /**
     * Test approximate equality with custom tolerances.
     */
    public function testApproxEqualCustomTolerances(): void
    {
        $r = new Rational(100, 1);
        $f = 100.5;

        // Within loose tolerance
        $this->assertTrue($r->approxEqual($f, 0.01, 1.0));

        // Outside tight tolerance
        $this->assertFalse($r->approxEqual($f, 1e-6, 0.1));
    }

    /**
     * Test approximate equality with very small values.
     */
    public function testApproxEqualVerySmallValues(): void
    {
        $r = new Rational(1, PHP_INT_MAX);
        $f = 2.0 / PHP_INT_MAX;

        // Within default absolute tolerance
        $this->assertTrue($r->approxEqual($f));
    }

    /**
     * Test approximate equality with very large values.
     */
    public function testApproxEqualVeryLargeValues(): void
    {
        $r = new Rational(PHP_INT_MAX, 1);
        $f = (float) PHP_INT_MAX;

        // Should be approximately equal
        $this->assertTrue($r->approxEqual($f));
    }

    #endregion

    #region Method approxCompare() tests.

    /**
     * Test approxCompare with values that are approximately equal.
     */
    public function testApproxCompareEqual(): void
    {
        $r1 = new Rational(1, 3);
        $r2 = new Rational(333333, 1000000);

        // Should return 0 (approximately equal) with looser tolerance
        $this->assertSame(0, $r1->approxCompare($r2, 1e-5, 1e-5));
    }

    /**
     * Test approxCompare with less than.
     */
    public function testApproxCompareLessThan(): void
    {
        $r1 = new Rational(1, 4);
        $r2 = new Rational(1, 2);

        // Should return -1 (less than)
        $this->assertSame(-1, $r1->approxCompare($r2));
    }

    /**
     * Test approxCompare with greater than.
     */
    public function testApproxCompareGreaterThan(): void
    {
        $r1 = new Rational(3, 4);
        $r2 = new Rational(1, 2);

        // Should return 1 (greater than)
        $this->assertSame(1, $r1->approxCompare($r2));
    }

    /**
     * Test approxCompare with custom tolerances.
     */
    public function testApproxCompareCustomTolerances(): void
    {
        $r1 = new Rational(1, 3);
        $r2 = new Rational(333, 1000);

        // With loose tolerance, should be equal
        $this->assertSame(0, $r1->approxCompare($r2, 0.01, 0.001));

        // With tight tolerance, should be greater than (1/3 > 333/1000)
        $this->assertSame(1, $r1->approxCompare($r2, 1e-6, 1e-6));
    }

    /**
     * Test approxCompare with Rational.
     */
    public function testApproxCompareWithRational(): void
    {
        $r1 = new Rational(1, 3);
        $r2 = new Rational(333333, 1000000);

        // Should be approximately equal with looser tolerance
        $this->assertSame(0, $r1->approxCompare($r2, 1e-5, 1e-5));
    }

    /**
     * Test approxCompare with invalid type throws InvalidArgumentException.
     */
    public function testApproxCompareInvalidTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $r = new Rational(3, 4);
        $r->approxCompare('string');
    }

    /**
     * Test approxCompare with NAN throws DomainException. approxCompare() tries approxEqual() first, which now
     * throws for NAN rather than returning false, so the exception surfaces before compare() would even run.
     */
    public function testApproxCompareWithNanThrows(): void
    {
        $this->expectException(DomainException::class);
        $r = new Rational(3, 4);
        $r->approxCompare(NAN);
    }

    /**
     * Test approxCompare with ±INF. approxEqual() returns false for infinity, so this falls through to compare(),
     * which gives ±INF its well-defined ordering.
     */
    public function testApproxCompareWithInfinity(): void
    {
        $r = new Rational(3, 4);

        $this->assertSame(-1, $r->approxCompare(INF));
        $this->assertSame(1, $r->approxCompare(-INF));
    }

    #endregion
}
