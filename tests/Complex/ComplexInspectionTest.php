<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Complex::class)]
class ComplexInspectionTest extends TestCase
{
    /**
     * Test isReal for real numbers.
     */
    public function testIsRealTrue(): void
    {
        $z1 = new Complex(5, 0);
        $this->assertTrue($z1->isReal());

        $z2 = new Complex(-3.14, 0);
        $this->assertTrue($z2->isReal());

        $z3 = new Complex(0, 0);
        $this->assertTrue($z3->isReal());
    }

    /**
     * Test isReal for complex numbers.
     */
    public function testIsRealFalse(): void
    {
        $z1 = new Complex(3, 4);
        $this->assertFalse($z1->isReal());

        $z2 = new Complex(0, 1);
        $this->assertFalse($z2->isReal());

        $z3 = new Complex(5, 0.0000001);
        $this->assertFalse($z3->isReal());
    }
}
