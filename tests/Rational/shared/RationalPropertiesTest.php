<?php

declare(strict_types=1);

namespace Mossy2100\Math\Tests\Rational;

use Mossy2100\Math\Rational;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rational::class)]
class RationalPropertiesTest extends TestCase
{
    #region Property $numerator tests.

    /**
     * Test $numerator reflects the value passed to the constructor, in canonical (reduced) form.
     */
    public function testNumerator(): void
    {
        $r1 = new Rational(3, 4);
        $this->assertSame(3, $r1->numerator);

        // Reduced to canonical form.
        $r2 = new Rational(6, 8);
        $this->assertSame(3, $r2->numerator);

        // PHPStan catches write attempts at static analysis time; at runtime, private(set) prevents
        // modification.
    }

    #endregion

    #region Property $denominator tests.

    /**
     * Test $denominator reflects the value passed to the constructor, in canonical (reduced) form,
     * and is always positive.
     */
    public function testDenominator(): void
    {
        $r1 = new Rational(3, 4);
        $this->assertSame(4, $r1->denominator);

        // Reduced to canonical form.
        $r2 = new Rational(6, 8);
        $this->assertSame(4, $r2->denominator);

        // A negative denominator is normalized to positive (with the sign moved to the numerator).
        $r3 = new Rational(3, -4);
        $this->assertSame(4, $r3->denominator);

        // PHPStan catches write attempts at static analysis time; at runtime, private(set) prevents
        // modification.
    }

    #endregion
}
