<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Complex;

use DomainException;
use OceanMoon\Core\Floats;
use OceanMoon\Math\Complex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Complex::class)]
class ComplexRootTest extends TestCase
{
    #region Method roots() tests.

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
            $this->assertEqualsWithDelta(1.0, $cubed->real, EPSILON);
            $this->assertEqualsWithDelta(0.0, $cubed->imaginary, EPSILON);
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

        $this->assertEqualsWithDelta(0.0, $root1->real, EPSILON);
        $this->assertTrue(
            Floats::approxEqual($root1->imaginary, 1.0) || Floats::approxEqual($root1->imaginary, -1.0)
        );
        $this->assertEqualsWithDelta(0.0, $root2->real, EPSILON);
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
        $this->assertEqualsWithDelta(0.0, $roots[0]->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $roots[0]->imaginary, EPSILON);
    }

    #endregion

    #region Method sqrt() tests.

    /**
     * Test sqrt (principal square root).
     */
    public function testSqrt(): void
    {
        // sqrt(4) = 2
        $result = new Complex(4)->sqrt();
        $this->assertEqualsWithDelta(2.0, $result->real, EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->imaginary, EPSILON);

        // sqrt(-1) = i (principal value)
        $result2 = new Complex(-1)->sqrt();
        $this->assertEqualsWithDelta(0.0, $result2->real, EPSILON);
        $this->assertEqualsWithDelta(1.0, $result2->imaginary, EPSILON);

        // General complex values (off the real/imaginary axes), exercising the phase/2 path.
        // sqrt(3 + 4i) = 2 + i, since (2 + i)² = 3 + 4i.
        $result3 = new Complex(3, 4)->sqrt();
        $this->assertEqualsWithDelta(2.0, $result3->real, EPSILON);
        $this->assertEqualsWithDelta(1.0, $result3->imaginary, EPSILON);

        // sqrt(3 - 4i) = 2 - i (negative phase), since (2 - i)² = 3 - 4i.
        $result4 = new Complex(3, -4)->sqrt();
        $this->assertEqualsWithDelta(2.0, $result4->real, EPSILON);
        $this->assertEqualsWithDelta(-1.0, $result4->imaginary, EPSILON);

        // sqrt(-3 + 4i) = 1 + 2i (second-quadrant input, phase near π), since (1 + 2i)² = -3 + 4i.
        $result5 = new Complex(-3, 4)->sqrt();
        $this->assertEqualsWithDelta(1.0, $result5->real, EPSILON);
        $this->assertEqualsWithDelta(2.0, $result5->imaginary, EPSILON);
    }

    #endregion
}
