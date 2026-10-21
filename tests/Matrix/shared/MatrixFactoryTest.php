<?php

declare(strict_types=1);

namespace Mossy2100\Math\Tests\Matrix;

use DomainException;
use InvalidArgumentException;
use LengthException;
use Mossy2100\Math\Matrix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixFactoryTest extends TestCase
{
    #region Method fromArray() tests.

    /**
     * Test fromArray with a valid 2D array.
     */
    public function testFromArrayWithValidData(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $this->assertSame(2, $m->rowCount);
        $this->assertSame(3, $m->columnCount);
        $this->assertSame(1.0, $m->get(0, 0));
        $this->assertSame(6.0, $m->get(1, 2));
    }

    /**
     * Test fromArray with float values.
     */
    public function testFromArrayWithFloats(): void
    {
        $m = Matrix::fromArray([
            [1.5, 2.5],
            [3.5, 4.5],
        ]);
        $this->assertSame(1.5, $m->get(0, 0));
        $this->assertSame(4.5, $m->get(1, 1));
    }

    /**
     * Test fromArray with an empty array creates a 0x0 matrix.
     */
    public function testFromArrayWithEmptyArray(): void
    {
        $m = Matrix::fromArray([]);
        $this->assertSame(0, $m->rowCount);
        $this->assertSame(0, $m->columnCount);
    }

    /**
     * Test fromArray with a non-array row throws InvalidArgumentException.
     */
    public function testFromArrayWithNonArrayRowThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create Matrix from array. Row 0 must be an array.');
        Matrix::fromArray([1, 2, 3]);
    }

    /**
     * Data provider for non-numeric element values.
     *
     * @return array<string, list<mixed>>
     */
    public static function nonNumericElementProvider(): array
    {
        return [
            'string'         => ['two'],
            'numeric string' => ['2'],
            'bool'           => [true],
            'null'           => [null],
            'array'          => [
                [1, 2],
            ],
            'object'         => [
                new Matrix(1, 1),
            ],
        ];
    }

    /**
     * Test fromArray with a non-numeric element throws InvalidArgumentException, without the value in the message.
     *
     * @param mixed $value The non-numeric element value.
     */
    #[DataProvider('nonNumericElementProvider')]
    public function testFromArrayWithNonNumericValuesThrows(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Cannot create Matrix from array. Element at row 0, column 1 must be an int or float.'
        );
        Matrix::fromArray([
            [1, $value, 3],
        ]);
    }

    /**
     * Test fromArray with ragged rows throws LengthException.
     */
    public function testFromArrayWithRaggedRowsThrows(): void
    {
        $this->expectException(LengthException::class);
        Matrix::fromArray([
            [1, 2, 3],
            [4, 5],
        ]);
    }

    /**
     * Test fromArray with the outer array not a list (non-sequential keys) throws DomainException.
     */
    public function testFromArrayWithNonListOuterArrayThrows(): void
    {
        $this->expectException(DomainException::class);
        Matrix::fromArray([
            5 => [1, 2],
            9 => [3, 4],
        ]);
    }

    /**
     * Test fromArray with a row that is not a list (non-sequential keys) throws DomainException.
     */
    public function testFromArrayWithNonListRowThrows(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot create Matrix from array. Row 1 must be a list.');
        Matrix::fromArray([
            [1, 2],
            [
                5 => 3,
                9 => 4,
            ],
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
            '/^Cannot create Matrix from array\. Element at row 0, column 1 \((INF|-INF|NAN)\) must be finite\.$/'
        );
        Matrix::fromArray([
            [1.0, $value],
        ]);
    }

    #endregion

    #region Method identity() tests.

    /**
     * Test identity matrix 1x1.
     */
    public function testIdentityOneByOne(): void
    {
        $idMat = Matrix::identity(1);
        $this->assertSame(1, $idMat->rowCount);
        $this->assertSame(1, $idMat->columnCount);
        $this->assertSame(1.0, $idMat->get(0, 0));
    }

    /**
     * Test identity matrix 3x3.
     */
    public function testIdentityThreeByThree(): void
    {
        $idMat = Matrix::identity(3);
        $this->assertSame(3, $idMat->rowCount);
        $this->assertSame(3, $idMat->columnCount);

        // Verify diagonal is 1 and off-diagonal is 0.
        for ($i = 0; $i < 3; $i++) {
            for ($j = 0; $j < 3; $j++) {
                if ($i === $j) {
                    $this->assertSame(1.0, $idMat->get($i, $j));
                } else {
                    $this->assertSame(0.0, $idMat->get($i, $j));
                }
            }
        }
    }

    #endregion
}
