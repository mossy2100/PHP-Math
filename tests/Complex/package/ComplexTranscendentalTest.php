<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use DomainException;
use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use const OceanMoon\Core\Globals\M_TAU;

#[CoversClass(Complex::class)]
class ComplexTranscendentalTest extends TestCase
{
    /**
     * Test natural logarithm of various special values.
     */
    public function testLnSpecialValues(): void
    {
        // ln(1) = 0
        $result = new Complex(1)->ln();
        $this->assertEqualsWithDelta(0.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // ln(e) = 1
        $result2 = new Complex(M_E)->ln();
        $this->assertEqualsWithDelta(1.0, $result2->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result2->imaginary, EPSILON);

        // ln(2) = M_LN2
        $result3 = new Complex(2)->ln();
        $this->assertEqualsWithDelta(M_LN2, $result3->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result3->imaginary, EPSILON);

        // ln(10) = M_LN10
        $result4 = new Complex(10)->ln();
        $this->assertEqualsWithDelta(M_LN10, $result4->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result4->imaginary, EPSILON);

        // ln(π) = M_LNPI
        $result5 = new Complex(M_PI)->ln();
        $this->assertEqualsWithDelta(M_LNPI, $result5->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result5->imaginary, EPSILON);
    }

    /**
     * Test ln of complex numbers.
     */
    public function testLnComplex(): void
    {
        // ln(z) = ln|z| + i*arg(z)
        $z = new Complex(3, 4);
        $result = $z->ln();

        /** @var float $mag */
        $mag = $z->magnitude;

        $expectedReal = log($mag);
        $expectedImag = $z->phase;

        $this->assertEqualsWithDelta($expectedReal, $result->real, EPSILON);
        $this->assertEqualsWithDelta($expectedImag, $result->imaginary, EPSILON);
    }

    /**
     * Test ln(0) throws exception.
     */
    public function testLnZero(): void
    {
        $this->expectException(DomainException::class);
        new Complex(0)->ln();
    }

    /**
     * Test logarithm with various bases.
     */
    public function testLogReals(): void
    {
        // log_2(8) = 3
        $result = new Complex(8)->log(2);
        $this->assertEqualsWithDelta(3.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // log_10(100) = 2
        $result2 = new Complex(100)->log(10);
        $this->assertEqualsWithDelta(2.0, $result2->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result2->imaginary, EPSILON);

        // log_e(e) = 1 (natural log)
        $result3 = new Complex(M_E)->log(M_E);
        $this->assertEqualsWithDelta(1.0, $result3->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result3->imaginary, EPSILON);

        // log_2(e)
        $result4 = new Complex(M_E)->log(2);
        $this->assertEqualsWithDelta(M_LOG2E, $result4->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result4->imaginary, EPSILON);

        // log_10(e)
        $result5 = new Complex(M_E)->log(10);
        $this->assertEqualsWithDelta(M_LOG10E, $result5->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result5->imaginary, EPSILON);
    }

    /**
     * Test log with complex numbers.
     */
    public function testLogComplex(): void
    {
        // log_b(z) = ln(z) / ln(b)
        $z = new Complex(3, 4);
        $base = new Complex(2, 1);

        $result = $z->log($base);

        // Verify using the change of base formula
        $lnZ = $z->ln();
        $lnBase = $base->ln();
        $expected = $lnZ->div($lnBase);

        $this->assertEqualsWithDelta($expected->real, $result->real, EPSILON);
        $this->assertEqualsWithDelta($expected->imaginary, $result->imaginary, EPSILON);
    }

    /**
     * Test log with base 0 throws exception.
     */
    public function testLogBaseZero(): void
    {
        $this->expectException(DomainException::class);
        new Complex(5)->log(0);
    }

    /**
     * Test log with base 1 throws exception.
     */
    public function testLogBaseOne(): void
    {
        $this->expectException(DomainException::class);
        new Complex(5)->log(1);
    }

    /**
     * Test exponential function.
     */
    public function testExpSpecialValues(): void
    {
        // e^0 = 1
        $result = new Complex(0)->exp();
        $this->assertEqualsWithDelta(1.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // e^1 = e
        $result2 = new Complex(1)->exp();
        $this->assertEqualsWithDelta(M_E, $result2->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result2->imaginary, EPSILON);

        // e^ln(2) = 2
        $result3 = new Complex(M_LN2)->exp();
        $this->assertEqualsWithDelta(2.0, $result3->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result3->imaginary, EPSILON);

        // e^ln(10) = 10
        $result4 = new Complex(M_LN10)->exp();
        $this->assertEqualsWithDelta(10.0, $result4->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result4->imaginary, EPSILON);

        // e^ln(π) = π
        $result5 = new Complex(M_LNPI)->exp();
        $this->assertEqualsWithDelta(M_PI, $result5->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result5->imaginary, EPSILON);
    }

    /**
     * Test Euler's identity, both forms.
     */
    public function testEulersIdentity(): void
    {
        // e^πi = -1
        $result5 = new Complex(0, M_PI)->exp();
        $this->assertEqualsWithDelta(-1, $result5->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result5->imaginary, EPSILON);

        // e^τi = 1
        $result5 = new Complex(0, M_TAU)->exp();
        $this->assertEqualsWithDelta(1, $result5->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result5->imaginary, EPSILON);
    }
}
