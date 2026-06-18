<?php

declare(strict_types=1);

namespace Complex;

use DomainException;
use OceanMoon\Core\Floats;
use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Complex::class)]
class ComplexPowerTest extends TestCase
{
    /**
     * Test power with integer exponents.
     */
    public function testPowInteger(): void
    {
        // (3 + 4i)^2
        $z = new Complex(3, 4);
        $result = $z->pow(2);

        // (3 + 4i)^2 = 9 + 24i + 16i² = 9 + 24i - 16 = -7 + 24i
        $this->assertEqualsWithDelta(-7.0, $result->real, Complex::EPSILON);
        $this->assertEqualsWithDelta(24.0, $result->imaginary, Complex::EPSILON);

        // z^0 = 1
        $result2 = $z->pow(0);
        $this->assertEqualsWithDelta(1.0, $result2->real, Complex::EPSILON);
        $this->assertEqualsWithDelta(0.0, $result2->imaginary, Complex::EPSILON);

        // z^1 = z
        $result3 = $z->pow(1);
        $this->assertEqualsWithDelta(3.0, $result3->real, Complex::EPSILON);
        $this->assertEqualsWithDelta(4.0, $result3->imaginary, Complex::EPSILON);
    }

    /**
     * Test i^2 = -1 using sqr().
     */
    public function testISquared(): void
    {
        $result = Complex::i()->sqr();

        $this->assertEqualsWithDelta(-1.0, $result->real, Complex::EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, Complex::EPSILON);
    }

    /**
     * Test i^2 = -1 using pow(2), which has a special-case shortcut.
     */
    public function testISquaredViaPow(): void
    {
        $result = Complex::i()->pow(2);

        $this->assertEqualsWithDelta(-1.0, $result->real, Complex::EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, Complex::EPSILON);
    }

    /**
     * Test pow(-1) delegates to inv().
     */
    public function testPowNegativeOne(): void
    {
        // (3 + 4i)^(-1) = 1/(3 + 4i) = (3 - 4i)/25
        $z = new Complex(3, 4);
        $result = $z->pow(-1);
        $expected = $z->inv();

        $this->assertEqualsWithDelta($expected->real, $result->real, Complex::EPSILON);
        $this->assertEqualsWithDelta($expected->imaginary, $result->imaginary, Complex::EPSILON);

        // Verify actual values: 3/25 - 4i/25
        $this->assertEqualsWithDelta(0.12, $result->real, Complex::EPSILON);
        $this->assertEqualsWithDelta(-0.16, $result->imaginary, Complex::EPSILON);
    }

    /**
     * Test e^w shortcut.
     */
    public function testPowEBase(): void
    {
        $w = new Complex(2, 3);
        $result = new Complex(M_E)->pow($w);

        // e^(2+3i) should equal exp(2+3i)
        $expected = $w->exp();

        $this->assertEqualsWithDelta($expected->real, $result->real, Complex::EPSILON);
        $this->assertEqualsWithDelta($expected->imaginary, $result->imaginary, Complex::EPSILON);
    }

    /**
     * Test 0^0 returns 1 (conventional).
     */
    public function testZeroPowerZero(): void
    {
        $result = new Complex(0)->pow(0);

        $this->assertEqualsWithDelta(1.0, $result->real, Complex::EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, Complex::EPSILON);
    }

    /**
     * Test 0 raised to positive real returns 0.
     */
    public function testZeroPowerPositive(): void
    {
        $result = new Complex(0)->pow(5);

        $this->assertEqualsWithDelta(0.0, $result->real, Complex::EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, Complex::EPSILON);
    }

    /**
     * Test 0 raised to negative real throws exception.
     */
    public function testZeroPowerNegative(): void
    {
        $this->expectException(DomainException::class);
        new Complex(0)->pow(-2);
    }

    /**
     * Test 0 raised to complex throws exception.
     */
    public function testZeroPowerComplex(): void
    {
        $this->expectException(DomainException::class);
        new Complex(0)->pow(new Complex(1, 1));
    }

    /**
     * Test sqr() squares a complex number.
     */
    public function testSqr(): void
    {
        // (3 + 4i)² = 9 + 24i - 16 = -7 + 24i
        $z = new Complex(3, 4);
        $result = $z->sqr();

        $this->assertEqualsWithDelta(-7.0, $result->real, Complex::EPSILON);
        $this->assertEqualsWithDelta(24.0, $result->imaginary, Complex::EPSILON);
    }

    /**
     * Test sqr() with a purely imaginary number.
     */
    public function testSqrImaginary(): void
    {
        // (2i)² = -4
        $z = new Complex(0, 2);
        $result = $z->sqr();

        $this->assertEqualsWithDelta(-4.0, $result->real, Complex::EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, Complex::EPSILON);
    }

    /**
     * Test sqr() is equivalent to pow(2).
     */
    public function testSqrEqualsPowTwo(): void
    {
        $z = new Complex(5, -3);
        $sqr = $z->sqr();
        $pow2 = $z->pow(2);

        $this->assertEqualsWithDelta($sqr->real, $pow2->real, Complex::EPSILON);
        $this->assertEqualsWithDelta($sqr->imaginary, $pow2->imaginary, Complex::EPSILON);
    }

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
            $this->assertEqualsWithDelta(1.0, $cubed->real, Complex::EPSILON);
            $this->assertEqualsWithDelta(0.0, $cubed->imaginary, Complex::EPSILON);
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

        $this->assertEqualsWithDelta(0.0, $root1->real, Complex::EPSILON);
        $this->assertTrue(
            Floats::approxEqual($root1->imaginary, 1.0) || Floats::approxEqual($root1->imaginary, -1.0)
        );
        $this->assertEqualsWithDelta(0.0, $root2->real, Complex::EPSILON);
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
        $this->assertEqualsWithDelta(0.0, $roots[0]->real, Complex::EPSILON);
        $this->assertEqualsWithDelta(0.0, $roots[0]->imaginary, Complex::EPSILON);
    }

    /**
     * Test sqrt (principal square root).
     */
    public function testSqrt(): void
    {
        // sqrt(4) = 2
        $result = new Complex(4)->sqrt();
        $this->assertEqualsWithDelta(2.0, $result->real, Complex::EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, Complex::EPSILON);

        // sqrt(-1) = i (principal value)
        $result2 = new Complex(-1)->sqrt();
        $this->assertEqualsWithDelta(0.0, $result2->real, Complex::EPSILON);
        $this->assertEqualsWithDelta(1.0, $result2->imaginary, Complex::EPSILON);
    }
}
