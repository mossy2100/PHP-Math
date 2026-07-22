<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Vector;

use OceanMoon\Math\Vector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Vector::class)]
class VectorUnaryArithmeticTest extends TestCase
{
    #region Method neg() tests.

    /**
     * Test negating a vector.
     */
    public function testNeg(): void
    {
        $v = Vector::fromArray([1, -2, 3]);
        $result = $v->neg();
        $this->assertSame([-1.0, 2.0, -3.0], $result->toArray());
    }

    /**
     * Test negating a zero vector returns a zero vector.
     */
    public function testNegZeroVector(): void
    {
        $v = new Vector(3);
        $result = $v->neg();
        $this->assertSame([0.0, 0.0, 0.0], $result->toArray());
    }

    #endregion
}
