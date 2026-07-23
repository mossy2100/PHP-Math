<?php

declare(strict_types=1);

namespace OceanMoon\Math\Tests\Matrix;

use OceanMoon\Math\Matrix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
class MatrixConversionTest extends TestCase
{
    #region Method toArray() tests.

    /**
     * Test toArray returns the correct 2D array.
     */
    public function testToArray(): void
    {
        $m = Matrix::fromArray([
            [1, 2, 3],
            [4, 5, 6],
        ]);
        $this->assertSame([
            [1.0, 2.0, 3.0],
            [4.0, 5.0, 6.0],
        ], $m->toArray());
    }

    #endregion

    #region Method __toString() tests.

    /**
     * Test __toString uses box-drawing characters.
     */
    public function testToStringUsesBoxDrawingCharacters(): void
    {
        $m = Matrix::fromArray([
            [1, 2],
            [3, 4],
        ]);
        $str = (string) $m;
        $this->assertStringContainsString("\u{250C}", $str); // top-left corner
        $this->assertStringContainsString("\u{2510}", $str); // top-right corner
        $this->assertStringContainsString("\u{2514}", $str); // bottom-left corner
        $this->assertStringContainsString("\u{2518}", $str); // bottom-right corner
        $this->assertStringContainsString("\u{2502}", $str); // vertical bar
    }

    /**
     * Test __toString with an empty matrix.
     */
    public function testToStringWithEmptyMatrix(): void
    {
        $m = new Matrix(0, 0);
        $str = (string) $m;
        $this->assertStringContainsString("\u{250C}", $str);
        $this->assertStringContainsString("\u{2518}", $str);
    }

    /**
     * Test __toString alignment with mixed-width numbers.
     */
    public function testToStringAlignmentWithMixedWidthNumbers(): void
    {
        $m = Matrix::fromArray([
            [1, 200],
            [30, 4],
        ]);
        $str = (string) $m;
        // The wider number (200) should pad narrower numbers.
        $this->assertStringContainsString('200', $str);
        $this->assertStringContainsString('1', $str);

        // Each row should have the same visual width between the vertical bars.
        $lines = explode("\n", $str);
        // Lines 1 and 2 are data rows (index 1 and 2 of the array).
        $this->assertSame(strlen($lines[1]), strlen($lines[2]));
    }

    /**
     * Test that __toString() doesn't render floating-point representation noise from arithmetic.
     * PHP's default (string) cast on a float is governed by the `precision` ini setting (14 by
     * default), which already collapses IEEE-754 quirks like 0.1 + 0.2 == 0.30000000000000004 down
     * to '0.3' rather than rendering 17-digit garbage that would dominate the column-width
     * calculation.
     */
    public function testToStringSuppressesFloatingPointNoise(): void
    {
        $m = Matrix::fromArray([
            [0.1 + 0.2, 1],
            [2, 0.1 + 0.2],
        ]);
        $str = (string) $m;

        // Cells should render as '0.3', not '0.30000000000000004'.
        $this->assertStringContainsString('0.3', $str);
        $this->assertStringNotContainsString('0.30000000000000004', $str);

        // Column width should be governed by the longest cleanly-formatted cell ('0.3' = 3
        // characters), not by the 19-character noise representation. Each data row should be
        // shorter than what raw float-to-string would produce.
        $lines = explode("\n", $str);
        $this->assertLessThan(20, strlen($lines[1]));
    }

    #endregion
}
