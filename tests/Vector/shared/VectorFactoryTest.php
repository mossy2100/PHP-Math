<?php

declare(strict_types=1);

namespace Mossy2100\Math\Tests\Vector;

use DomainException;
use InvalidArgumentException;
use Mossy2100\Math\Vector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Vector::class)]
class VectorFactoryTest extends TestCase
{
    #region Method fromArray() tests.

    /**
     * Test fromArray with integer values.
     */
    public function testFromArrayWithInts(): void
    {
        $v = Vector::fromArray([1, 2, 3]);
        $this->assertSame([1.0, 2.0, 3.0], $v->toArray());
        $this->assertSame(3, $v->count);
    }

    /**
     * Test fromArray with float values.
     */
    public function testFromArrayWithFloats(): void
    {
        $v = Vector::fromArray([1.5, 2.5, 3.5]);
        $this->assertSame([1.5, 2.5, 3.5], $v->toArray());
    }

    /**
     * Test fromArray with mixed int and float values.
     */
    public function testFromArrayWithMixed(): void
    {
        $v = Vector::fromArray([1, 2.5, 3]);
        $this->assertSame([1.0, 2.5, 3.0], $v->toArray());
    }

    /**
     * Test fromArray with empty array creates a count-zero vector.
     */
    public function testFromArrayWithEmptyArray(): void
    {
        $v = Vector::fromArray([]);
        $this->assertSame(0, $v->count);
        $this->assertSame([], $v->toArray());
    }

    /**
     * Test fromArray() stores negative zeros as positive zeros.
     */
    public function testFromArrayNormalizesNegativeZero(): void
    {
        $v = Vector::fromArray([-0.0, 1]);
        $this->assertSame(['0', '1'], array_map(strval(...), $v->toArray()));
    }

    /**
     * Data provider for non-numeric element values.
     *
     * @return array<string, list<mixed>>
     */
    public static function nonNumericElementProvider(): array
    {
        return [
            'string'         => ['hello'],
            'numeric string' => ['2'],
            'bool'           => [true],
            'null'           => [null],
            'array'          => [
                [1, 2],
            ],
            'object'         => [
                new Vector(2),
            ],
        ];
    }

    /**
     * Test fromArray with a non-numeric element throws InvalidArgumentException, without the value in the message.
     *
     * @param mixed $value The non-numeric element value.
     */
    #[DataProvider('nonNumericElementProvider')]
    public function testFromArrayWithNonNumericThrows(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create Vector from array. Element at index 1 must be an int or float.');
        Vector::fromArray([1, $value, 3]);
    }

    /**
     * Test fromArray with a non-sequential (non-list) array throws DomainException.
     */
    public function testFromArrayWithNonSequentialArrayThrows(): void
    {
        $this->expectException(DomainException::class);
        Vector::fromArray([
            5  => 10,
            10 => 20,
            15 => 30,
        ]);
    }

    /**
     * Data provider for non-finite element values.
     *
     * @return array<string, list<float>>
     */
    public static function nonFiniteElementProvider(): array
    {
        return [
            'positive infinity' => [INF],
            'negative infinity' => [-INF],
            'NAN'               => [NAN],
        ];
    }

    /**
     * Test fromArray with a non-finite element throws DomainException.
     *
     * @param float $value The non-finite element value.
     */
    #[DataProvider('nonFiniteElementProvider')]
    public function testFromArrayWithNonFiniteElementThrows(float $value): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches(
            '/^Cannot create Vector from array\. Element at index 1 \((INF|-INF|NAN)\) must be finite\.$/'
        );
        Vector::fromArray([1.0, $value]);
    }

    #endregion
}
