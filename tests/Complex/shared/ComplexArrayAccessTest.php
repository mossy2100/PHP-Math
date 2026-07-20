<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use InvalidArgumentException;
use LogicException;
use OceanMoon\Math\Complex;
use OutOfRangeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Complex::class)]
class ComplexArrayAccessTest extends TestCase
{
    #region Method offsetExists() tests.

    /**
     * Test ArrayAccess offsetExists.
     */
    public function testOffsetExists(): void
    {
        $z = new Complex(3, 4);

        $this->assertTrue(isset($z[0]));
        $this->assertTrue(isset($z[1]));
        $this->assertFalse(isset($z[2]));
        $this->assertFalse(isset($z[-1]));
    }

    #endregion

    #region Method offsetGet() tests.

    /**
     * Test ArrayAccess offsetGet.
     */
    public function testOffsetGet(): void
    {
        $z = new Complex(3, 4);

        $this->assertSame(3.0, $z[0]);
        $this->assertSame(4.0, $z[1]);
    }

    /**
     * Test ArrayAccess offsetGet with invalid offset throws exception.
     */
    public function testOffsetGetInvalid(): void
    {
        $z = new Complex(3, 4);

        $this->expectException(OutOfRangeException::class);
        $value = $z[2];
    }

    /**
     * Test ArrayAccess offsetGet with a non-integer offset throws InvalidArgumentException.
     */
    public function testOffsetGetNonIntegerOffset(): void
    {
        $z = new Complex(3, 4);

        $this->expectException(InvalidArgumentException::class);
        $value = $z['real'];
    }

    #endregion

    #region Method offsetSet() tests.

    /**
     * Test ArrayAccess offsetSet throws exception (immutable).
     */
    public function testOffsetSetThrows(): void
    {
        $z = new Complex(3, 4);

        $this->expectException(LogicException::class);
        $z[0] = 5;
    }

    #endregion

    #region Method offsetUnset() tests.

    /**
     * Test ArrayAccess offsetUnset throws exception (immutable).
     */
    public function testOffsetUnsetThrows(): void
    {
        $z = new Complex(3, 4);

        $this->expectException(LogicException::class);
        unset($z[0]);
    }

    #endregion
}
