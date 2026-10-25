<?php

declare(strict_types=1);

namespace Mossy2100\Math\Tests\Rational;

use Mossy2100\Core\Exceptions\ArithmeticException;
use Mossy2100\Math\Rational;
use OverflowException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TypeError;

#[CoversClass(Rational::class)]
class RationalBinaryArithmeticTest extends TestCase
{
    #region Method add() tests.

    /**
     * Test addition with Rational.
     */
    public function testAddRational(): void
    {
        // 1/2 + 1/3 = 5/6
        $r1 = new Rational(1, 2);
        $r2 = new Rational(1, 3);
        $result = $r1->add($r2);

        $this->assertSame(5, $result->numerator);
        $this->assertSame(6, $result->denominator);

        // 3/4 + 1/4 = 1
        $r3 = new Rational(3, 4);
        $r4 = new Rational(1, 4);
        $result2 = $r3->add($r4);

        $this->assertSame(1, $result2->numerator);
        $this->assertSame(1, $result2->denominator);
    }

    /**
     * Test addition with integer.
     */
    public function testAddInteger(): void
    {
        // 1/2 + 2 = 5/2
        $r = new Rational(1, 2);
        $result = $r->add(2);

        $this->assertSame(5, $result->numerator);
        $this->assertSame(2, $result->denominator);
    }

    /**
     * Test add() does not modify the original (immutability).
     */
    public function testAddDoesNotMutate(): void
    {
        $r = new Rational(3, 4);

        $r2 = $r->add(new Rational(1, 4));

        $this->assertNotSame($r, $r2);
        $this->assertSame(3, $r->numerator);
        $this->assertSame(4, $r->denominator);
        $this->assertSame(1, $r2->numerator);
        $this->assertSame(1, $r2->denominator);
    }

    /**
     * Test add() throws when the result overflows an int.
     */
    public function testAddOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot add the given Rational numbers. The result would overflow.');
        $r = new Rational(PHP_INT_MAX, 2);
        $r->add(new Rational(1, 3));
    }

    /**
     * Test add() doesn't overflow when the product of the denominators would, but the result fits.
     */
    public function testAddLargeDenominators(): void
    {
        $result = new Rational(1, 6000000000)->add(new Rational(1, 4000000000));

        $this->assertSame(1, $result->numerator);
        $this->assertSame(2400000000, $result->denominator);
    }

    /**
     * Test add() doesn't overflow when the cross products would, but the integer parts cancel.
     */
    public function testAddLargeCancellingValues(): void
    {
        // PHP_INT_MAX/3 + -(PHP_INT_MAX - 1)/2 = (3 - PHP_INT_MAX)/6.
        $result = new Rational(PHP_INT_MAX, 3)->add(new Rational(1 - PHP_INT_MAX, 2));
        $expected = new Rational(3 - PHP_INT_MAX, 6);

        $this->assertSame($expected->numerator, $result->numerator);
        $this->assertSame($expected->denominator, $result->denominator);
    }

    /**
     * Test add() with an argument of the wrong type throws TypeError, naming the accepted types.
     */
    public function testAddInvalidTypeThrows(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('must be of type Mossy2100\\Math\\Rational|int, string given');

        $x = new Rational(1, 2);
        $x->add('x'); // @phpstan-ignore argument.type
    }

    #endregion

    #region Method sub() tests.

    /**
     * Test subtraction with Rational.
     */
    public function testSubRational(): void
    {
        // 3/4 - 1/4 = 1/2
        $r1 = new Rational(3, 4);
        $r2 = new Rational(1, 4);
        $result = $r1->sub($r2);

        $this->assertSame(1, $result->numerator);
        $this->assertSame(2, $result->denominator);

        // 1/2 - 3/4 = -1/4
        $r3 = new Rational(1, 2);
        $r4 = new Rational(3, 4);
        $result2 = $r3->sub($r4);

        $this->assertSame(-1, $result2->numerator);
        $this->assertSame(4, $result2->denominator);
    }

    /**
     * Test subtraction with integer.
     */
    public function testSubInteger(): void
    {
        // 5/2 - 2 = 1/2
        $r = new Rational(5, 2);
        $result = $r->sub(2);

        $this->assertSame(1, $result->numerator);
        $this->assertSame(2, $result->denominator);
    }

    /**
     * Test sub() does not modify the original (immutability).
     */
    public function testSubDoesNotMutate(): void
    {
        $r = new Rational(3, 4);

        $r->sub(new Rational(1, 4));

        $this->assertSame(3, $r->numerator);
        $this->assertSame(4, $r->denominator);
    }

    /**
     * Test sub() throws when the result overflows an int.
     */
    public function testSubOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot multiply the given Rational numbers. The result would overflow.');
        $this->expectExceptionMessage('Cannot subtract the given Rational numbers. The result would overflow.');
        $r = new Rational(PHP_INT_MAX, 2);
        $r->sub(new Rational(1, 3));
    }

    /**
     * Test sub() doesn't overflow when the product of the denominators would, but the result fits.
     */
    public function testSubLargeDenominators(): void
    {
        $result = new Rational(1, 4000000000)->sub(new Rational(1, 6000000000));

        $this->assertSame(1, $result->numerator);
        $this->assertSame(12000000000, $result->denominator);
    }

    /**
     * Test sub() doesn't overflow when the cross products would, but the integer parts cancel.
     */
    public function testSubLargeCancellingValues(): void
    {
        // PHP_INT_MAX/3 - (PHP_INT_MAX - 1)/2 = (3 - PHP_INT_MAX)/6.
        $result = new Rational(PHP_INT_MAX, 3)->sub(new Rational(PHP_INT_MAX - 1, 2));
        $expected = new Rational(3 - PHP_INT_MAX, 6);

        $this->assertSame($expected->numerator, $result->numerator);
        $this->assertSame($expected->denominator, $result->denominator);
    }

    #endregion

    #region Method mul() tests.

    /**
     * Test multiplication with Rational.
     */
    public function testMulRational(): void
    {
        // 2/3 * 3/4 = 1/2
        $r1 = new Rational(2, 3);
        $r2 = new Rational(3, 4);
        $result = $r1->mul($r2);

        $this->assertSame(1, $result->numerator);
        $this->assertSame(2, $result->denominator);

        // 3/5 * 5/7 = 3/7
        $r3 = new Rational(3, 5);
        $r4 = new Rational(5, 7);
        $result2 = $r3->mul($r4);

        $this->assertSame(3, $result2->numerator);
        $this->assertSame(7, $result2->denominator);
    }

    /**
     * Test multiplication with integer.
     */
    public function testMulInteger(): void
    {
        // 2/3 * 6 = 4
        $r = new Rational(2, 3);
        $result = $r->mul(6);

        $this->assertSame(4, $result->numerator);
        $this->assertSame(1, $result->denominator);
    }

    /**
     * Test multiplication with zero.
     */
    public function testMulZero(): void
    {
        $r = new Rational(3, 4);
        $result = $r->mul(0);

        $this->assertSame(0, $result->numerator);
        $this->assertSame(1, $result->denominator);
    }

    /**
     * Test mul() does not modify the original (immutability).
     */
    public function testMulDoesNotMutate(): void
    {
        $r = new Rational(3, 4);

        $r->mul(2);

        $this->assertSame(3, $r->numerator);
        $this->assertSame(4, $r->denominator);
    }

    /**
     * Test mul() throws when the product overflows an int, even after cross-cancellation. The operands share no
     * common factors, so cross-cancellation cannot avert the overflow.
     */
    public function testMulOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $r = new Rational(PHP_INT_MAX);
        $r->mul(2);
    }

    #endregion

    #region Method div() tests.

    /**
     * Test division with Rational.
     */
    public function testDivRational(): void
    {
        // (2/3) / (3/4) = 8/9
        $r1 = new Rational(2, 3);
        $r2 = new Rational(3, 4);
        $result = $r1->div($r2);

        $this->assertSame(8, $result->numerator);
        $this->assertSame(9, $result->denominator);
    }

    /**
     * Test division with integer.
     */
    public function testDivInteger(): void
    {
        // (3/4) / 2 = 3/8
        $r = new Rational(3, 4);
        $result = $r->div(2);

        $this->assertSame(3, $result->numerator);
        $this->assertSame(8, $result->denominator);
    }

    /**
     * Test dividing zero by a non-zero value short-circuits to 0, without going through the
     * general cross-cancellation path.
     */
    public function testDivZeroNumerator(): void
    {
        // (0/7) / 3 = 0/1
        $r = new Rational(0, 7);
        $result = $r->div(3);

        $this->assertSame(0, $result->numerator);
        $this->assertSame(1, $result->denominator);
    }

    /**
     * Test division where the numerators share a common factor, exercising the gcd(a,c)
     * cross-cancellation branch.
     */
    public function testDivCancelsCommonNumeratorFactor(): void
    {
        // (4/5) / (6/7): numerators 4 and 6 share a factor of 2, so gcd(a,c) = 2.
        // Cross-cancelling gives (2/5) * (7/3) = 14/15.
        $r1 = new Rational(4, 5);
        $r2 = new Rational(6, 7);
        $result = $r1->div($r2);

        $this->assertSame(14, $result->numerator);
        $this->assertSame(15, $result->denominator);
    }

    /**
     * Test division where the denominators share a common factor, exercising the gcd(b,d)
     * cross-cancellation branch.
     */
    public function testDivCancelsCommonDenominatorFactor(): void
    {
        // (3/4) / (5/6): denominators 4 and 6 share a factor of 2, so gcd(b,d) = 2.
        // Cross-cancelling gives (3/2) * (3/5) = 9/10.
        $r1 = new Rational(3, 4);
        $r2 = new Rational(5, 6);
        $result = $r1->div($r2);

        $this->assertSame(9, $result->numerator);
        $this->assertSame(10, $result->denominator);
    }

    /**
     * Test division by zero throws exception.
     */
    public function testDivZeroThrows(): void
    {
        $this->expectException(ArithmeticException::class);
        $r = new Rational(3, 4);
        $r->div(0);
    }

    /**
     * Test that zero divided by an argument of the wrong type throws TypeError, rather than returning zero.
     */
    public function testDivZeroByInvalidTypeThrows(): void
    {
        $this->expectException(TypeError::class);
        $r = new Rational(0);
        $r->div('x'); // @phpstan-ignore argument.type
    }

    /**
     * Test div() does not modify the original (immutability).
     */
    public function testDivDoesNotMutate(): void
    {
        $r = new Rational(3, 4);

        $r->div(2);

        $this->assertSame(3, $r->numerator);
        $this->assertSame(4, $r->denominator);
    }

    /**
     * Test div() throws when the product overflows an int, even after cross-cancellation. The operands share no
     * common factors, so cross-cancellation cannot avert the overflow.
     */
    public function testDivOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot divide the given Rational numbers. The result would overflow.');
        $r = new Rational(PHP_INT_MAX);
        $r->div(new Rational(1, 2));
    }

    #endregion

    #region Method mod() tests.

    /**
     * Data provider for testMod(): the dividend, the divisor, and the expected remainder.
     *
     * @return array<string, array{string, string, string}>
     */
    public static function modProvider(): array
    {
        return [
            'both positive' => ['7/2', '2', '3/2'],
            'negative dividend' => ['-7/2', '2', '-3/2'],
            'negative divisor' => ['7/2', '-2', '3/2'],
            'both negative' => ['-7/2', '-2', '-3/2'],
            'fractional divisor' => ['-1/2', '1/3', '-1/6'],
            'exact multiple' => ['9/2', '3/2', '0'],
            'zero dividend' => ['0', '-3/4', '0'],
            'smaller than divisor' => ['-1/3', '-2', '-1/3'],
        ];
    }

    /**
     * Test mod() for every combination of signs: the result has the sign of the dividend.
     */
    #[DataProvider('modProvider')]
    public function testMod(string $dividend, string $divisor, string $expected): void
    {
        $result = Rational::fromString($dividend)->mod(Rational::fromString($divisor));

        $this->assertInstanceOf(Rational::class, $result);
        $this->assertSame($expected, (string) $result);
    }

    /**
     * Test mod() with an int divisor, including -1 with a dividend near PHP_INT_MIN.
     */
    public function testModInt(): void
    {
        $this->assertEquals(new Rational(-2), new Rational(-5)->mod(3));
        $this->assertEquals(new Rational(-2), new Rational(-5)->mod(-3));
        $this->assertEquals(new Rational(0), new Rational(PHP_INT_MIN + 1)->mod(-1));
    }

    /**
     * Test mod() by zero throws ArithmeticException, for an int and a Rational.
     */
    public function testModByZeroThrows(): void
    {
        $r = new Rational(3, 4);

        foreach (
            [0, new Rational(0)] as $zero
        ) {
            try {
                $r->mod($zero);
                $this->fail('Expected ArithmeticException.');
            } catch (ArithmeticException $e) {
                $this->assertSame('Cannot divide by zero.', $e->getMessage());
            }
        }
    }

    /**
     * Test mod() with large operands, where the remainder is representable, though the dividend over the common
     * denominator isn't.
     */
    public function testModLargeOperands(): void
    {
        $this->assertEquals(new Rational(0), new Rational(PHP_INT_MAX)->mod(new Rational(1, 2)));
        $this->assertEquals(new Rational(1, 2), new Rational(1, 2)->mod(PHP_INT_MAX));
        $this->assertEquals(new Rational(5), new Rational(5)->mod(PHP_INT_MIN));

        // PHP_INT_MAX - 1 is divisible by 3, so 1/(PHP_INT_MAX - 1) divides PHP_INT_MAX/3 exactly.
        $this->assertEquals(new Rational(0), new Rational(PHP_INT_MAX, 3)->mod(new Rational(1, PHP_INT_MAX - 1)));
    }

    /**
     * Test mod() throws OverflowException when the remainder's denominator is too large.
     */
    public function testModOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage(
            'Cannot find the remainder of the given Rational numbers. The result would overflow.'
        );

        // 4000000007 and 4000000009 are prime, so the remainder, 2/(4000000007 × 4000000009), is in lowest terms, and
        // its denominator is about 1.6e19.
        new Rational(1, 4000000007)->mod(new Rational(1, 4000000009));
    }

    /**
     * Test mod() with an argument of the wrong type, including a float, throws TypeError.
     */
    public function testModInvalidTypeThrows(): void
    {
        $this->expectException(TypeError::class);
        $r = new Rational(3, 4);
        $r->mod(1.5); // @phpstan-ignore argument.type
    }

    /**
     * Test mod() does not modify the original (immutability).
     */
    public function testModDoesNotMutate(): void
    {
        $r = new Rational(7, 2);
        $r->mod(2);

        $this->assertSame(7, $r->numerator);
        $this->assertSame(2, $r->denominator);
    }

    #endregion
}
