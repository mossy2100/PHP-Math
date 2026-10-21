<?php

declare(strict_types=1);

namespace Mossy2100\Math\Tests\Vector;

use Mossy2100\Core\Exceptions\ArithmeticException;
use Mossy2100\Math\Vector;
use OverflowException;
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

    /**
     * Test negating zeros doesn't give negative zeros. assertSame() treats -0.0 and 0.0 as equal, so compare the
     * strings, where -0.0 is "-0".
     */
    public function testNegHasNoNegativeZeros(): void
    {
        $result = Vector::fromArray([0, 1])->neg();
        $this->assertSame(['0', '-1'], array_map(strval(...), $result->toArray()));
    }

    #endregion

    #region Method hadamardInv() tests.

    /**
     * Test the Hadamard inverse (element-wise reciprocal) of a vector.
     */
    public function testHadamardInv(): void
    {
        $v = Vector::fromArray([2, 4, 5]);
        $result = $v->hadamardInv();
        $this->assertEqualsWithDelta([0.5, 0.25, 0.2], $result->toArray(), EPSILON);
    }

    /**
     * Test the Hadamard inverse of a vector containing a zero element throws ArithmeticException.
     */
    public function testHadamardInvOfZeroElementThrows(): void
    {
        $v = Vector::fromArray([1, 0, 2]);
        $this->expectException(ArithmeticException::class);
        $v->hadamardInv();
    }

    /**
     * Test hadamardInv() throws when the result overflows.
     */
    public function testHadamardInvOverflowThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot compute reciprocal of the given Vector. The result would overflow.');
        Vector::fromArray([1e-320])->hadamardInv();
    }

    #endregion
}
