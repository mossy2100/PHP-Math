<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use DomainException;
use OceanMoon\Core\Floats;
use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Complex::class)]
class ComplexRootTest extends TestCase
{
    #region Method roots() tests.

    /**
     * Test nth roots.
     */
    public function testRoots(): void
    {
        // Cube roots of 1
        $z = new Complex(1);
        $roots = $z->roots(3);

        $this->assertCount(3, $roots);

        // Verify all roots satisfy z^3 = 1
        foreach ($roots as $root) {
            $cubed = $root->pow(3);
            $this->assertEqualsWithDelta(1.0, $cubed->real, EPSILON);
            $this->assertEqualsWithDelta(0.0, $cubed->imaginary, EPSILON);
        }
    }

    /**
     * Test square roots of -1 (should be ±i).
     */
    public function testRootsOfMinusOne(): void
    {
        $z = new Complex(-1);
        $roots = $z->roots(2);

        $this->assertCount(2, $roots);

        // One root should be i, the other -i
        [$root1, $root2] = $roots;

        $this->assertEqualsWithDelta(0.0, $root1->real, EPSILON);
        $this->assertTrue(
            Floats::approxEqual($root1->imaginary, 1.0) || Floats::approxEqual($root1->imaginary, -1.0)
        );
        $this->assertEqualsWithDelta(0.0, $root2->real, EPSILON);
        $this->assertTrue(
            Floats::approxEqual($root2->imaginary, 1.0) || Floats::approxEqual($root2->imaginary, -1.0)
        );
    }

    /**
     * Test roots with invalid n throws exception.
     */
    public function testRootsInvalidN(): void
    {
        $this->expectException(DomainException::class);
        new Complex(1)->roots(0);
    }

    /**
     * Test roots of zero.
     */
    public function testRootsOfZero(): void
    {
        $roots = new Complex(0)->roots(3);

        $this->assertCount(1, $roots);
        $this->assertEqualsWithDelta(0.0, $roots[0]->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $roots[0]->imaginary, EPSILON);
    }

    /**
     * Test roots of degree 1 returns this number exactly.
     */
    public function testRootsDegreeOne(): void
    {
        $roots = new Complex(3.7, -1.2)->roots(1);

        $this->assertCount(1, $roots);
        $this->assertComplexExact(3.7, -1.2, $roots[0]);
    }

    /**
     * Test the square roots are sqrt() and its negative, with no negative zeros.
     */
    public function testRootsDegreeTwoMatchesSqrt(): void
    {
        $roots = new Complex(-1)->roots(2);
        $this->assertComplexExact(0.0, 1.0, $roots[0]);
        $this->assertComplexExact(0.0, -1.0, $roots[1]);

        $roots = new Complex(4)->roots(2);
        $this->assertComplexExact(2.0, 0.0, $roots[0]);
        $this->assertComplexExact(-2.0, 0.0, $roots[1]);

        $roots = new Complex(3, 4)->roots(2);
        $this->assertComplexExact(2.0, 1.0, $roots[0]);
        $this->assertComplexExact(-2.0, -1.0, $roots[1]);

        $roots = new Complex(0, 2)->roots(2);
        $this->assertComplexExact(1.0, 1.0, $roots[0]);
        $this->assertComplexExact(-1.0, -1.0, $roots[1]);
    }

    /**
     * Test roots that lie on an axis are exact.
     */
    public function testRootsExactOnAxes(): void
    {
        // The 4th roots of 1 are 1, i, -1 and -i.
        $roots = new Complex(1)->roots(4);
        $this->assertComplexExact(1.0, 0.0, $roots[0]);
        $this->assertComplexExact(0.0, 1.0, $roots[1]);
        $this->assertComplexExact(-1.0, 0.0, $roots[2]);
        $this->assertComplexExact(0.0, -1.0, $roots[3]);

        // The 4th roots of 16 are 2, 2i, -2 and -2i.
        $roots = new Complex(16)->roots(4);
        $this->assertComplexExact(2.0, 0.0, $roots[0]);
        $this->assertComplexExact(0.0, 2.0, $roots[1]);
        $this->assertComplexExact(-2.0, 0.0, $roots[2]);
        $this->assertComplexExact(0.0, -2.0, $roots[3]);

        // The real cube root of -8 is -2.
        $roots = new Complex(-8)->roots(3);
        $this->assertComplexExact(-2.0, 0.0, $roots[1]);

        // One cube root of i is -i.
        $roots = new Complex(0, 1)->roots(3);
        $this->assertComplexExact(0.0, -1.0, $roots[2]);

        // Two 6th roots of -1 are i and -i.
        $roots = new Complex(-1)->roots(6);
        $this->assertComplexExact(0.0, 1.0, $roots[1]);
        $this->assertComplexExact(0.0, -1.0, $roots[4]);

        // The 4th roots of -16i don't lie on an axis, but each one raised to the 4th power is still -16i.
        foreach (new Complex(0, -16)->roots(4) as $root) {
            $power = $root->pow(4);
            $this->assertEqualsWithDelta(0.0, $power->real, 1e-12);
            $this->assertEqualsWithDelta(-16.0, $power->imaginary, 1e-12);
        }
    }

    /**
     * Test the root magnitude is exact for a perfect power, although 1/n is rounded.
     */
    public function testRootsExactMagnitude(): void
    {
        // 64 ** (1/3) is 3.9999999999999996.
        $roots = new Complex(-64)->roots(3);
        $this->assertComplexExact(-4.0, 0.0, $roots[1]);

        $roots = new Complex(32)->roots(5);
        $this->assertComplexExact(2.0, 0.0, $roots[0]);

        $roots = new Complex(0, 729)->roots(3);
        $this->assertComplexExact(0.0, -9.0, $roots[2]);

        // Roots that aren't on an axis have the exact magnitude too.
        foreach (new Complex(1000)->roots(3) as $root) {
            $this->assertEqualsWithDelta(10.0, $root->magnitude, 1e-14);
        }
    }

    /**
     * Test roots() just below the negative real axis starts from the -π side.
     */
    public function testRootsJustBelowNegativeRealAxis(): void
    {
        $roots = new Complex(-1, -1e-17)->roots(3);
        $this->assertEqualsWithDelta(0.5, $roots[0]->real, EPSILON);
        $this->assertEqualsWithDelta(-sqrt(3) / 2, $roots[0]->imaginary, EPSILON);
        $this->assertEqualsWithDelta(0.5, $roots[1]->real, EPSILON);
        $this->assertEqualsWithDelta(sqrt(3) / 2, $roots[1]->imaginary, EPSILON);
        $this->assertEqualsWithDelta(-1.0, $roots[2]->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $roots[2]->imaginary, EPSILON);
    }

    #endregion

    #region Method sqrt() tests.

    /**
     * Test sqrt (principal square root).
     */
    public function testSqrt(): void
    {
        // sqrt(4) = 2
        $result = new Complex(4)->sqrt();
        $this->assertEqualsWithDelta(2.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // sqrt(-1) = i (principal value)
        $result2 = new Complex(-1)->sqrt();
        $this->assertEqualsWithDelta(0.0, $result2->real, EPSILON);
        $this->assertEqualsWithDelta(1.0, $result2->imaginary, EPSILON);

        // General complex values (off the real/imaginary axes), exercising the phase/2 path.
        // sqrt(3 + 4i) = 2 + i, since (2 + i)² = 3 + 4i.
        $result3 = new Complex(3, 4)->sqrt();
        $this->assertEqualsWithDelta(2.0, $result3->real, EPSILON);
        $this->assertEqualsWithDelta(1.0, $result3->imaginary, EPSILON);

        // sqrt(3 - 4i) = 2 - i (negative phase), since (2 - i)² = 3 - 4i.
        $result4 = new Complex(3, -4)->sqrt();
        $this->assertEqualsWithDelta(2.0, $result4->real, EPSILON);
        $this->assertEqualsWithDelta(-1.0, $result4->imaginary, EPSILON);

        // sqrt(-3 + 4i) = 1 + 2i (second-quadrant input, phase near π), since (1 + 2i)² = -3 + 4i.
        $result5 = new Complex(-3, 4)->sqrt();
        $this->assertEqualsWithDelta(1.0, $result5->real, EPSILON);
        $this->assertEqualsWithDelta(2.0, $result5->imaginary, EPSILON);
    }

    /**
     * Data provider for testSqrtExact().
     *
     * @return array<string, array{float, float, float, float}>
     */
    public static function sqrtExactProvider(): array
    {
        return [
            'zero'                         => [0.0, 0.0, 0.0, 0.0],
            'positive real'                => [4.0, 0.0, 2.0, 0.0],
            'positive real, negative zero' => [4.0, -0.0, 2.0, 0.0],
            'negative real'                => [-1.0, 0.0, 0.0, 1.0],
            'negative real, larger'        => [-4.0, 0.0, 0.0, 2.0],
            'negative real, negative zero' => [-4.0, -0.0, 0.0, 2.0],
            'positive imaginary'           => [0.0, 2.0, 1.0, 1.0],
            'negative imaginary'           => [0.0, -2.0, 1.0, -1.0],
            'positive imaginary, larger'   => [0.0, 8.0, 2.0, 2.0],
            'first quadrant'               => [3.0, 4.0, 2.0, 1.0],
            'second quadrant'              => [-3.0, 4.0, 1.0, 2.0],
            'third quadrant'               => [-3.0, -4.0, 1.0, -2.0],
            'fourth quadrant'              => [3.0, -4.0, 2.0, -1.0],
            'huge negative real'           => [-1.6e308, 0.0, 0.0, 1.2649110640673517e154],
            'tiny negative real'           => [-5e-324, 0.0, 0.0, 2.2227587494850775e-162],
        ];
    }

    /**
     * Test sqrt() is exact where the result is representable, including on the axes.
     */
    #[DataProvider('sqrtExactProvider')]
    public function testSqrtExact(float $real, float $imag, float $expectedReal, float $expectedImag): void
    {
        $this->assertComplexExact($expectedReal, $expectedImag, new Complex($real, $imag)->sqrt());
    }

    /**
     * Test sqrt() of a number near the float limit doesn't overflow.
     */
    public function testSqrtHuge(): void
    {
        $z = new Complex(1e308, 1e308);
        $result = $z->sqrt()->sqr();

        $this->assertEqualsWithDelta(1.0, $result->real / $z->real, 1e-15);
        $this->assertEqualsWithDelta(1.0, $result->imaginary / $z->imaginary, 1e-15);
    }

    /**
     * Test sqrt() of a subnormal number keeps its precision.
     */
    public function testSqrtTiny(): void
    {
        $result = new Complex(0, 5e-324)->sqrt();
        $expected = sqrt(5e-324) * M_SQRT1_2;

        $this->assertEqualsWithDelta(1.0, $result->real / $expected, 1e-15);
        $this->assertEqualsWithDelta(1.0, $result->imaginary / $expected, 1e-15);
    }

    /**
     * Inputs with both parts subnormal (or zero), and their square roots, from Python's cmath.sqrt().
     *
     * @return list<array{float, float, float, float}>
     */
    public static function sqrtSubnormalProvider(): array
    {
        return [
            [3.4099830507e-314, -1.53760452e-315, 1.8470829661634615e-157, -4.16225083123664e-159],
            [-5e-324, 5e-324, 1.0115549693666347e-162, 2.4421097261308304e-162],
            [1e-310, 0.0, 9.999999999999986e-156, 0.0],
            [-1e-310, -2e-315, 1.0000000009020136e-160, -1.0000000000499984e-155],
            [4.9e-322, -1e-320, 7.24602263686294e-161, -6.9002604414689885e-161],
        ];
    }

    /**
     * Test sqrt() is accurate when both parts are subnormal, so |z| itself has lost precision.
     */
    #[DataProvider('sqrtSubnormalProvider')]
    public function testSqrtBothPartsSubnormal(float $real, float $imag, float $expectedReal, float $expectedImag): void
    {
        $result = new Complex($real, $imag)->sqrt();

        $this->assertEqualsWithDelta(1.0, $result->real / $expectedReal, 1e-15);
        if ($expectedImag === 0.0) {
            $this->assertSame(0.0, $result->imaginary);
        } else {
            $this->assertEqualsWithDelta(1.0, $result->imaginary / $expectedImag, 1e-15);
        }
    }

    #endregion

    #region Helper methods

    /**
     * Assert a Complex has exactly the given parts, including the sign of a zero part.
     */
    private function assertComplexExact(float $expectedReal, float $expectedImag, Complex $actual): void
    {
        $this->assertSame($expectedReal, $actual->real);
        $this->assertSame($expectedImag, $actual->imaginary);
        $this->assertSame(Floats::isNegativeZero($expectedReal), Floats::isNegativeZero($actual->real));
        $this->assertSame(Floats::isNegativeZero($expectedImag), Floats::isNegativeZero($actual->imaginary));
    }

    #endregion
}
