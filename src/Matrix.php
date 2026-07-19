<?php

declare(strict_types=1);

namespace OceanMoon\Math;

use Countable;
use DomainException;
use InvalidArgumentException;
use LengthException;
use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Core\Floats;
use OceanMoon\Core\Traits\Comparison\ApproxEquatable;
use OutOfRangeException;
use Override;
use Stringable;

use function OceanMoon\Core\Globals\ex;
use function OceanMoon\Core\Globals\is_number;

/**
 * Encapsulates a 2-dimensional matrix and provides a number of useful methods.
 */
final class Matrix implements Stringable, Countable
{
    use ApproxEquatable;

    #region Properties

    #region Private properties

    /**
     * The matrix data.
     *
     * This must be private because even if it's private(set) if they can get $this->data they could add new elements
     * (inadvertently sizing the matrix without changing rowCount/colCount or making it non-rectangular) or they could
     * set elements to non-numbers.
     *
     * @var list<list<float>>
     */
    private array $data;

    #endregion

    #region Public properties (readonly)

    /**
     * The number of rows in the matrix.
     */
    private(set) int $rowCount;

    /**
     * The number of columns in the matrix.
     */
    private(set) int $columnCount;

    #endregion

    #endregion

    #region Constructor

    /**
     * Create a new matrix with the specified dimensions.
     *
     * @param int $rowCount Number of rows.
     * @param int $columnCount Number of columns.
     * @throws DomainException If dimensions are negative.
     */
    public function __construct(int $rowCount, int $columnCount)
    {
        // Check if dimensions are non-negative.
        if ($rowCount < 0) {
            throw new DomainException("Cannot create Matrix with $rowCount rows. Must not be negative.");
        }
        if ($columnCount < 0) {
            throw new DomainException("Cannot create Matrix with $columnCount columns. Must not be negative.");
        }

        // Initialize matrix properties.
        $this->rowCount = $rowCount;
        $this->columnCount = $columnCount;
        $this->data = array_fill(0, $rowCount, array_fill(0, $columnCount, 0.0));
    }

    #endregion

    #region Factory methods

    /**
     * Create a matrix from a rectangular array of numbers.
     *
     * If $arr is empty, it will have 0 rows and 0 columns.
     * If $arr is an array of empty arrays, the result will have that many rows and 0 columns.
     *
     * @param array<array-key, mixed> $arr Rectangular array of numbers.
     * @return self
     * @throws DomainException If the array or a row isn't a list, or an element isn't a number.
     * @throws LengthException If the rows don't all have the same number of columns.
     */
    public static function fromArray(array $arr): self
    {
        // Handle empty case.
        if (empty($arr)) {
            return new self(0, 0);
        }

        // Check the outer array is a list.
        if (!array_is_list($arr)) {
            throw new DomainException('Cannot create Matrix from array. Must be a list.');
        }

        $rowCount = count($arr);
        $columnCount = null;
        $data = [];

        // Validate data and ensure rectangular matrix.
        foreach ($arr as $i => $row) {
            // Check each row is a list array.
            if (!is_array($row) || !array_is_list($row)) {
                throw new DomainException("Cannot create Matrix from array. Row $i must be a list.");
            }

            // Check all rows have the same number of columns.
            $thisRowSize = count($row);
            if ($columnCount === null) {
                $columnCount = $thisRowSize;
            } elseif ($thisRowSize !== $columnCount) {
                throw new LengthException(
                    'Cannot create Matrix from array. All rows must have the same number of elements.'
                );
            }

            $dataRow = [];

            // Check each row contains only numbers.
            foreach ($row as $j => $value) {
                // Check if each value is a number.
                if (!is_number($value)) {
                    throw new DomainException("Invalid element type at row $i, column $j: " . get_debug_type($value) .
                        '. Must be int or float.');
                }

                // Convert the value to a float and store it in the matrix.
                $dataRow[] = (float) $value;
            }

            $data[] = $dataRow;
        }

        // Create the matrix.
        $matrix = new self($rowCount, $columnCount);
        $matrix->data = $data;

        return $matrix;
    }

    /**
     * Create an identity matrix of the specified size.
     *
     * @param int $size Size of the identity matrix.
     * @return self Identity matrix.
     * @throws DomainException If $size is negative.
     */
    public static function identity(int $size): self
    {
        $result = new self($size, $size);
        for ($i = 0; $i < $size; $i++) {
            $result->set($i, $i, 1);
        }
        return $result;
    }

    #endregion

    #region Conversion methods

    /**
     * Get a copy of the matrix data as a rectangular array.
     *
     * @return list<list<float>> Rectangular array of matrix elements.
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Convert the matrix to a string representation using box-drawing characters.
     *
     * @return string String representation of the Matrix.
     */
    #[Override] // Stringable
    public function __toString(): string
    {
        if ($this->rowCount === 0 || $this->columnCount === 0) {
            return '┌ ┐' . "\n" . '└ ┘';
        }

        // Format every cell up front so column widths are calculated against the same strings that get rendered.
        $cells = [];
        $maxWidth = 0;
        for ($i = 0; $i < $this->rowCount; $i++) {
            $cells[$i] = [];
            for ($j = 0; $j < $this->columnCount; $j++) {
                $cell = (string) $this->data[$i][$j];
                $cells[$i][$j] = $cell;
                $maxWidth = max($maxWidth, strlen($cell));
            }
        }

        // Top border.
        $innerWidth = $this->columnCount * ($maxWidth + 2);
        $result = '┌' . str_repeat(' ', $innerWidth) . '┐' . "\n";

        // Data rows.
        for ($i = 0; $i < $this->rowCount; $i++) {
            $result .= '│ ';
            for ($j = 0; $j < $this->columnCount; $j++) {
                if ($j > 0) {
                    $result .= '  ';
                }
                $result .= str_pad($cells[$i][$j], $maxWidth, ' ', STR_PAD_LEFT);
            }
            $result .= ' │' . "\n";
        }

        // Bottom border.
        $result .= '└' . str_repeat(' ', $innerWidth) . '┘';

        return $result;
    }

    #endregion

    #region Inspection methods

    /**
     * Check if the matrix is square, optionally of a specific size.
     *
     * @param int|null $size If specified, check for exact size, otherwise any size.
     * @return bool True if square, false otherwise.
     */
    public function isSquare(?int $size = null): bool
    {
        return ($this->rowCount === $this->columnCount) && ($size === null || $this->rowCount === $size);
    }

    /**
     * Get a matrix element.
     *
     * @param int $row Row index (0-based).
     * @param int $col Column index (0-based).
     * @return float Value of the matrix element.
     * @throws OutOfRangeException If indexes are outside valid range.
     */
    public function get(int $row, int $col): float
    {
        // Check if indexes are within bounds.
        if ($row < 0 || $row >= $this->rowCount) {
            throw new OutOfRangeException(
                "Invalid row index: $row. Must be in the range 0-" . ($this->rowCount - 1) . '.'
            );
        }
        if ($col < 0 || $col >= $this->columnCount) {
            throw new OutOfRangeException(
                "Invalid column index: $col. Must be in the range 0-" . ($this->columnCount - 1) . '.'
            );
        }

        return $this->data[$row][$col];
    }

    /**
     * Get a row as a vector.
     *
     * @param int $row Row index (0-based).
     * @return Vector Row vector.
     * @throws OutOfRangeException If row index is outside valid range.
     */
    public function getRow(int $row): Vector
    {
        // Check if row index is within bounds.
        if ($row < 0 || $row >= $this->rowCount) {
            throw new OutOfRangeException(
                "Invalid row index: $row. Must be in the range 0-" . ($this->rowCount - 1) . '.'
            );
        }

        return Vector::fromArray($this->data[$row]);
    }

    /**
     * Get a column as a vector.
     *
     * @param int $col Column index (0-based).
     * @return Vector Column vector.
     * @throws OutOfRangeException If column index is outside valid range.
     */
    public function getColumn(int $col): Vector
    {
        // Check if column index is within bounds.
        if ($col < 0 || $col >= $this->columnCount) {
            throw new OutOfRangeException(
                "Invalid column index: $col. Must be in the range 0-" . ($this->columnCount - 1) . '.'
            );
        }

        $column = [];
        for ($i = 0; $i < $this->rowCount; $i++) {
            $column[] = $this->data[$i][$col];
        }

        return Vector::fromArray($column);
    }

    /**
     * Extract a rectangular sub-matrix: a copy of a subset of this matrix's elements.
     *
     * @param int $row Row of the top-left corner of the region to copy (0-based).
     * @param int $col Column of the top-left corner of the region to copy (0-based).
     * @param int $rowCount Number of rows to copy (sub-matrix height).
     * @param int $colCount Number of columns to copy (sub-matrix width).
     * @return self A new matrix containing the copied elements.
     * @throws OutOfRangeException If either count is negative, or the selected region extends beyond this matrix's
     * bounds.
     */
    public function copy(int $row, int $col, int $rowCount, int $colCount): self
    {
        // Check the row range is valid.
        if ($row < 0 || $rowCount < 0 || $row + $rowCount > $this->rowCount) {
            throw new OutOfRangeException(
                "Invalid row range: $row-" . ($row + $rowCount) . '. Must be within 0-'
                . ($this->rowCount - 1) . '.'
            );
        }

        // Check the column range is valid.
        if ($col < 0 || $colCount < 0 || $col + $colCount > $this->columnCount) {
            throw new OutOfRangeException(
                "Invalid column range: $col-" . ($col + $colCount) . '. Must be within 0-'
                . ($this->columnCount - 1) . '.'
            );
        }

        // Copy the selected elements into a new matrix.
        $result = new self($rowCount, $colCount);
        for ($i = 0; $i < $rowCount; $i++) {
            for ($j = 0; $j < $colCount; $j++) {
                $result->set($i, $j, $this->data[$row + $i][$col + $j]);
            }
        }

        return $result;
    }

    #endregion

    #region Modification methods

    /**
     * Set a matrix element.
     *
     * @param int $row Row index (0-based).
     * @param int $col Column index (0-based).
     * @param float $value Value to set.
     * @throws OutOfRangeException If indexes are outside valid range.
     * @throws DomainException If the value is not finite (±INF or NAN).
     */
    public function set(int $row, int $col, float $value): void
    {
        // Check if indexes are within bounds.
        if ($row < 0 || $row >= $this->rowCount) {
            throw new OutOfRangeException(
                "Invalid row index: $row. Must be in the range 0-" . ($this->rowCount - 1) . '.'
            );
        }
        if ($col < 0 || $col >= $this->columnCount) {
            throw new OutOfRangeException(
                "Invalid column index: $col. Must be in the range 0-" . ($this->columnCount - 1) . '.'
            );
        }

        // Check the value is finite.
        if (!is_finite($value)) {
            throw new DomainException('Cannot set element to non-finite value: ' . ex($value) . '.');
        }

        assert($row < count($this->data) && $col < count($this->data[$row]));
        $this->data[$row][$col] = $value;
    }

    /**
     * Set a Matrix row from a row Vector.
     *
     * @param int $row Row index (0-based).
     * @param Vector $vec The row Vector.
     * @throws OutOfRangeException If row index is outside valid range.
     * @throws LengthException If the Vector is the wrong size.
     */
    public function setRow(int $row, Vector $vec): void
    {
        // Check if row index is within bounds.
        if ($row < 0 || $row >= $this->rowCount) {
            throw new OutOfRangeException(
                "Invalid row index: $row. Must be in the range 0-" . ($this->rowCount - 1) . '.'
            );
        }

        // Check length.
        $vectorSize = count($vec);
        if ($vectorSize !== $this->columnCount) {
            throw new LengthException(
                "Cannot set row due to incorrect Vector size: $vectorSize. Expected {$this->columnCount}."
            );
        }

        // Set values.
        $this->data[$row] = $vec->toArray();
    }

    /**
     * Set a column from a Vector or array.
     *
     * @param int $col Column index (0-based).
     * @param Vector $vec The column values.
     * @throws OutOfRangeException If column index is outside valid range.
     * @throws LengthException If the value has the wrong number of elements.
     */
    public function setColumn(int $col, Vector $vec): void
    {
        // Check if column index is within bounds.
        if ($col < 0 || $col >= $this->columnCount) {
            throw new OutOfRangeException(
                "Invalid column index: $col. Must be in the range 0-" . ($this->columnCount - 1) . '.'
            );
        }

        // Check length.
        $vectorSize = count($vec);
        if ($vectorSize !== $this->rowCount) {
            throw new LengthException(
                "Cannot set column due to incorrect Vector size: $vectorSize. Expected {$this->rowCount}."
            );
        }

        // Set values.
        for ($row = 0; $row < $this->rowCount; $row++) {
            assert($row < count($this->data) && $col < count($this->data[$row]));
            $this->data[$row][$col] = $vec[$row];
        }
    }

    /**
     * Copy the elements of another Matrix into this one, starting at the given position.
     *
     * Unlike most methods in this class, this one mutates the Matrix in place, matching set().
     *
     * @param self $other Matrix to paste. Must fit within this Matrix at the given offset.
     * @param int $row Row at which to place the top-left corner of $other (0-based). Defaults to 0.
     * @param int $col Column at which to place the top-left corner of $other (0-based). Defaults to 0.
     * @throws OutOfRangeException If either offset is negative, or $other doesn't fit within this matrix at that
     * offset.
     */
    public function paste(self $other, int $row = 0, int $col = 0): void
    {
        // Check the row offset is valid.
        if ($row < 0 || $row + $other->rowCount > $this->rowCount) {
            throw new OutOfRangeException(
                "Invalid row range: $row-" . ($row + $other->rowCount) . '. Must be within 0-'
                . ($this->rowCount - 1) . '.'
            );
        }

        // Check the column offset is valid.
        if ($col < 0 || $col + $other->columnCount > $this->columnCount) {
            throw new OutOfRangeException(
                "Invalid column range: $col-" . ($col + $other->columnCount) . '. Must be within 0-'
                . ($this->columnCount - 1) . '.'
            );
        }

        // Copy the elements from $other into this matrix.
        for ($i = 0; $i < $other->rowCount; $i++) {
            for ($j = 0; $j < $other->columnCount; $j++) {
                $this->set($row + $i, $col + $j, $other->data[$i][$j]);
            }
        }
    }

    #endregion

    #region Comparison methods

    /**
     * Check if this Matrix equals another.
     *
     * Two matrices are equal if they have the same dimensions and all corresponding elements are exactly equal.
     *
     * @param mixed $other The Matrix to compare with.
     * @return bool True if the Matrixes have the same dimensions and all elements are equal.
     * @throws InvalidArgumentException If $other is not a Matrix.
     */
    /** @disregard P1128 */
    #[Override] // Equatable
    public function equal(mixed $other): bool
    {
        // The argument has to be mixed to align with the trait method being overridden, so we add our own type check.
        // If we don't have a Matrix, abort.
        if (!$other instanceof self) {
            throw new InvalidArgumentException(
                'Cannot compare Matrix with ' . get_debug_type($other) . '. Must be Matrix.'
            );
        }

        // Check sizes are equal.
        if ($this->rowCount !== $other->rowCount || $this->columnCount !== $other->columnCount) {
            return false;
        }

        // Check elements are equal.
        for ($i = 0; $i < $this->rowCount; $i++) {
            for ($j = 0; $j < $this->columnCount; $j++) {
                if ($this->data[$i][$j] !== $other->data[$i][$j]) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if this Matrix approximately equals another.
     *
     * Each pair of corresponding elements is compared using Floats::approxEqual(), which checks absolute tolerance
     * first, then relative tolerance.
     *
     * @param mixed $other The Matrix to compare with.
     * @param float $relTol The relative tolerance.
     * @param float $absTol The absolute tolerance.
     * @return bool True if the Matrixes have the same dimensions and all elements are approximately equal.
     * @throws InvalidArgumentException If $other is not a Matrix.
     * @throws DomainException If either tolerance is negative.
     * @see Floats::approxEqual()
     */
    #[Override] // ApproxEquatable
    public function approxEqual(
        mixed $other,
        float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
        float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
    ): bool {
        // The argument has to be mixed to align with the trait method being overridden, so we add our own type check.
        // If we don't have a Matrix, abort.
        if (!$other instanceof self) {
            throw new InvalidArgumentException(
                'Cannot compare Matrix with ' . get_debug_type($other) . '. Must be Matrix.'
            );
        }

        // Check sizes are equal.
        if ($this->rowCount !== $other->rowCount || $this->columnCount !== $other->columnCount) {
            return false;
        }

        // Check elements are approximately equal.
        for ($i = 0; $i < $this->rowCount; $i++) {
            for ($j = 0; $j < $this->columnCount; $j++) {
                if (!Floats::approxEqual($this->data[$i][$j], $other->data[$i][$j], $relTol, $absTol)) {
                    return false;
                }
            }
        }

        return true;
    }

    #endregion

    #region Transformation methods

    /**
     * Create a new matrix with the given dimensions, containing as much of this matrix's data as fits.
     *
     * The result is anchored at (0, 0): if the new dimensions are larger than this matrix's, the extra rows and/or
     * columns are zero-filled; if smaller, the excess rows/columns (from the bottom and/or right) are dropped. To
     * resize from a different corner, or to insert/remove a row or column at an arbitrary position, compose copy()
     * and paste() directly instead.
     *
     * @param int $rowCount The number of rows in the resized matrix.
     * @param int $columnCount The number of columns in the resized matrix.
     * @return self A new matrix with the given dimensions.
     * @throws DomainException If either dimension is negative.
     */
    public function resize(int $rowCount, int $columnCount): self
    {
        $result = new self($rowCount, $columnCount);
        $result->paste($this->copy(0, 0, min($this->rowCount, $rowCount), min($this->columnCount, $columnCount)));
        return $result;
    }

    #endregion

    #region Unary arithmetic methods

    /**
     * Negate this matrix.
     *
     * @return self A new matrix with all elements negated.
     */
    public function neg(): self
    {
        return $this->mul(-1);
    }

    /**
     * Calculate the inverse of this matrix using cofactor expansion with the adjugate matrix.
     *
     * Warning: This algorithm has O(n! × n²) time complexity due to the underlying cofactor expansion used for
     * determinant calculation. It is suitable for small matrices (up to ~10x10) but will be extremely slow for larger
     * ones.
     *
     * @return self New matrix representing the inverse.
     * @throws DomainException If matrix is not square.
     * @throws ArithmeticException If matrix is not invertible (zero determinant).
     */
    public function inv(): self
    {
        // Check if matrix is square.
        if (!$this->isSquare()) {
            throw new DomainException('Cannot invert non-square Matrix.');
        }

        // Calculate the inverse using cofactor expansion and the adjugate matrix.
        $det = $this->det();
        if ($det === 0.0) {
            throw new ArithmeticException('Cannot invert Matrix with zero determinant.');
        }

        $n = $this->rowCount;
        $adjugate = new self($n, $n);

        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $minor = $this->getMinor($i, $j);
                $cofactor = (($i + $j) % 2 === 0 ? 1 : -1) * $this->calcDet($minor);
                $adjugate->set($j, $i, $cofactor / $det); // Note: transposed
            }
        }

        return $adjugate;
    }

    #endregion

    #region Binary arithmetic methods

    /**
     * Add another matrix to this one.
     *
     * @param self $other Matrix to add.
     * @return self New matrix representing the sum.
     * @throws LengthException If matrices have different dimensions.
     */
    public function add(self $other): self
    {
        // Check if dimensions are the same.
        if ($this->rowCount !== $other->rowCount || $this->columnCount !== $other->columnCount) {
            throw new LengthException(
                "Cannot add Matrix of incorrect dimensions: {$other->rowCount}x{$other->columnCount}. " .
                "Expected {$this->rowCount}x{$this->columnCount}."
            );
        }

        // Add the matrices.
        $result = new self($this->rowCount, $this->columnCount);
        for ($i = 0; $i < $this->rowCount; $i++) {
            for ($j = 0; $j < $this->columnCount; $j++) {
                $result->set($i, $j, $this->data[$i][$j] + $other->data[$i][$j]);
            }
        }
        return $result;
    }

    /**
     * Subtract another matrix from this one.
     *
     * @param self $other Matrix to subtract.
     * @return self New matrix representing the difference.
     * @throws LengthException If matrices have different dimensions.
     */
    public function sub(self $other): self
    {
        // Check if dimensions are the same.
        if ($this->rowCount !== $other->rowCount || $this->columnCount !== $other->columnCount) {
            throw new LengthException(
                "Cannot subtract Matrix of incorrect dimensions: {$other->rowCount}x{$other->columnCount}. " .
                "Expected {$this->rowCount}x{$this->columnCount}."
            );
        }

        // Subtract the matrices.
        $result = new self($this->rowCount, $this->columnCount);
        for ($i = 0; $i < $this->rowCount; $i++) {
            for ($j = 0; $j < $this->columnCount; $j++) {
                $result->set($i, $j, $this->data[$i][$j] - $other->data[$i][$j]);
            }
        }
        return $result;
    }

    /**
     * Multiply this matrix by a scalar or another matrix.
     *
     * To multiply by a Vector, use `mulVector()` instead.
     *
     * @param float|self $other Number or matrix to multiply by.
     * @return self New matrix representing the product.
     * @throws LengthException If dimensions are incompatible for multiplication.
     */
    public function mul(float|self $other): self
    {
        // Multiply matrix by a float.
        if (is_float($other)) {
            // Multiply each element of the matrix by the float.
            $scaled = new self($this->rowCount, $this->columnCount);
            for ($i = 0; $i < $this->rowCount; $i++) {
                for ($j = 0; $j < $this->columnCount; $j++) {
                    $scaled->set($i, $j, $this->data[$i][$j] * $other);
                }
            }
            return $scaled;
        }

        // Multiply matrix by a matrix.
        // Check if dimensions are compatible for multiplication.
        if ($this->columnCount !== $other->rowCount) {
            throw new LengthException(
                "Invalid Matrix row count: {$other->rowCount}. Must equal this Matrix's column count: " .
                "{$this->columnCount}."
            );
        }

        // Multiply the matrices.
        $result = new self($this->rowCount, $other->columnCount);
        for ($i = 0; $i < $this->rowCount; $i++) {
            for ($j = 0; $j < $other->columnCount; $j++) {
                $sum = 0.0;
                for ($k = 0; $k < $this->columnCount; $k++) {
                    $sum += $this->data[$i][$k] * $other->data[$k][$j];
                }
                $result->set($i, $j, $sum);
            }
        }

        return $result;
    }

    /**
     * Divide this matrix by a number or another matrix (A × B⁻¹).
     *
     * @param float|self $other Number or matrix to divide by.
     * @return self New matrix representing the quotient.
     * @throws ArithmeticException If dividing by zero, or by a non-invertible matrix (zero determinant).
     * @throws DomainException If dividing by a non-square matrix.
     * @throws LengthException If dividing by a matrix whose dimensions are incompatible for the resulting
     * multiplication.
     */
    public function div(float|self $other): self
    {
        // Check if dividing by a scalar.
        if (is_float($other)) {
            // Guard against division by zero.
            if ($other === 0.0) {
                throw new ArithmeticException('Cannot divide by zero.');
            }

            // Divide each element of the matrix by the scalar.
            $scaled = new self($this->rowCount, $this->columnCount);
            for ($i = 0; $i < $this->rowCount; $i++) {
                for ($j = 0; $j < $this->columnCount; $j++) {
                    $scaled->set($i, $j, $this->data[$i][$j] / $other);
                }
            }
            return $scaled;
        }

        // Multiply by the inverse.
        return $this->mul($other->inv());
    }

    /**
     * Calculate the Hadamard product (element-wise product) of this matrix with another.
     *
     * @param self $other Matrix to multiply element-wise with.
     * @return self New matrix representing the Hadamard product.
     * @throws LengthException If matrices have different dimensions.
     */
    public function hadamard(self $other): self
    {
        // Check if dimensions are the same.
        if ($this->rowCount !== $other->rowCount || $this->columnCount !== $other->columnCount) {
            throw new LengthException(
                "Cannot compute Hadamard product with Matrix of incorrect dimensions: " .
                "{$other->rowCount}x{$other->columnCount}. Expected {$this->rowCount}x{$this->columnCount}."
            );
        }

        // Multiply the matrices element-wise.
        $result = new self($this->rowCount, $this->columnCount);
        for ($i = 0; $i < $this->rowCount; $i++) {
            for ($j = 0; $j < $this->columnCount; $j++) {
                $result->set($i, $j, $this->data[$i][$j] * $other->data[$i][$j]);
            }
        }

        return $result;
    }

    #endregion

    #region Power methods

    /**
     * Raise this matrix to a power.
     *
     * @param int $exponent Power to raise to.
     * @return self New matrix representing the result.
     * @throws DomainException If matrix is not square.
     * @throws ArithmeticException If not invertible (zero determinant) for negative powers.
     */
    public function pow(int $exponent): self
    {
        // Check if matrix is square.
        if (!$this->isSquare()) {
            throw new DomainException('Cannot raise non-square Matrix to a power.');
        }

        // Handle power of 0.
        if ($exponent === 0) {
            return self::identity($this->rowCount);
        }

        // Handle power of 1.
        if ($exponent === 1) {
            return clone $this;
        }

        // Handle exponent = PHP_INT_MIN.
        if ($exponent === PHP_INT_MIN) {
            return $this->pow(PHP_INT_MAX)->mul($this)->inv();
        }

        // Handle negative powers.
        if ($exponent < 0) {
            return $this->inv()->pow(-$exponent);
        }

        // Handle positive powers greater than 1.
        $result = self::identity($this->rowCount);
        $base = clone $this;

        while ($exponent > 0) {
            if ($exponent % 2 === 1) {
                $result = $result->mul($base);
            }
            $base = $base->mul($base);
            $exponent = (int) ($exponent / 2);
        }

        return $result;
    }

    /**
     * Square this matrix.
     *
     * Equivalent to pow(2), but more efficient and readable.
     *
     * @return self A new matrix representing the square of this matrix.
     * @throws DomainException If the matrix is not square.
     */
    public function sqr(): self
    {
        if (!$this->isSquare()) {
            throw new DomainException('Cannot square non-square Matrix.');
        }

        return $this->mul($this);
    }

    #endregion

    #region Linear algebra methods

    /**
     * Multiply this matrix by a vector: Ax.
     *
     * The vector is treated as a column vector; its size must equal this matrix's column count. To go the other way
     * (xA), use `Vector::mulMatrix()` instead.
     *
     * @param Vector $vector The vector to multiply by.
     * @return Vector New vector representing the result.
     * @throws LengthException If the vector's size doesn't equal this matrix's column count.
     */
    public function mulVector(Vector $vector): Vector
    {
        return $this->mul($vector->toColumnMatrix())->getColumn(0);
    }

    /**
     * Get the transpose of this matrix.
     *
     * @return self New matrix representing the transpose.
     */
    public function transpose(): self
    {
        $result = new self($this->columnCount, $this->rowCount);
        for ($i = 0; $i < $this->rowCount; $i++) {
            for ($j = 0; $j < $this->columnCount; $j++) {
                $result->set($j, $i, $this->data[$i][$j]);
            }
        }

        return $result;
    }

    /**
     * Calculate the determinant of this matrix.
     *
     * @return float The determinant.
     * @throws DomainException If matrix is not square.
     */
    public function det(): float
    {
        // Check if matrix is square.
        if (!$this->isSquare()) {
            throw new DomainException('Cannot compute determinant of non-square Matrix.');
        }

        return $this->calcDet($this->data);
    }

    /**
     * Calculate the trace of this matrix (sum of diagonal elements).
     *
     * @return float The trace.
     * @throws DomainException If matrix is not square.
     */
    public function trace(): float
    {
        if (!$this->isSquare()) {
            throw new DomainException('Cannot compute trace of non-square Matrix.');
        }

        $sum = 0.0;
        for ($i = 0; $i < $this->rowCount; $i++) {
            $sum += $this->data[$i][$i];
        }

        return $sum;
    }

    #endregion

    #region Norm methods

    /**
     * Calculate the Frobenius norm (square root of the sum of all squared elements).
     *
     * This is the matrix analogue of the Euclidean norm for vectors.
     *
     * @return float The Frobenius norm.
     */
    public function norm(): float
    {
        $sum = 0.0;
        for ($i = 0; $i < $this->rowCount; $i++) {
            for ($j = 0; $j < $this->columnCount; $j++) {
                $sum += $this->data[$i][$j] ** 2;
            }
        }

        return sqrt($sum);
    }

    /**
     * Calculate the P1 norm (maximum absolute column sum).
     *
     * @return float The P1 norm.
     */
    public function p1Norm(): float
    {
        $max = 0.0;
        for ($j = 0; $j < $this->columnCount; $j++) {
            $colSum = 0.0;
            for ($i = 0; $i < $this->rowCount; $i++) {
                $colSum += abs($this->data[$i][$j]);
            }
            $max = max($max, $colSum);
        }

        return $max;
    }

    /**
     * Calculate the P-infinity norm (maximum absolute row sum).
     *
     * @return float The P-infinity norm.
     */
    public function pInfNorm(): float
    {
        $max = 0.0;
        for ($i = 0; $i < $this->rowCount; $i++) {
            $rowSum = 0.0;
            for ($j = 0; $j < $this->columnCount; $j++) {
                $rowSum += abs($this->data[$i][$j]);
            }
            $max = max($max, $rowSum);
        }

        return $max;
    }

    #endregion

    #region Aggregation methods

    /**
     * Get the number of elements in the Matrix.
     *
     * @return int
     */
    #[Override] // Countable
    public function count(): int
    {
        return $this->rowCount * $this->columnCount;
    }

    #endregion

    #region Helper methods

    /**
     * Recursive helper method to calculate determinant using cofactor expansion.
     *
     * Warning: This algorithm has O(n!) time complexity. It is suitable for small matrices (up to ~10x10) but will be
     * extremely slow for larger ones. For high-performance determinant calculation, consider LU decomposition (O(n³)).
     *
     * LU decomposition hasn't been implemented here because it's a bigger undertaking than the complexity numbers
     * alone suggest, for a case that's unlikely to be needed in practice:
     * - It requires partial pivoting (PA = LU, not plain LU) to avoid dividing by a zero pivot, which even a
     *   perfectly well-conditioned matrix can produce depending on element ordering.
     * - Pivoting means tracking row-swap parity to get the determinant's sign right (det = (-1)^swaps × product of
     *   U's diagonal), which is easy to get subtly wrong in a way that only shows up for specific pivot orderings.
     * - Distinguishing a genuinely singular matrix from a merely ill-conditioned one becomes an epsilon-tuning
     *   problem rather than an exact zero check.
     * - It only speeds up det() itself. inv() calls calcDet() once per minor via the adjugate method, so a faster
     *   calcDet() alone would still leave inv() at O(n² × n³); making inv() fast for large matrices needs its own
     *   separate rewrite (e.g. Gauss-Jordan elimination on an augmented matrix).
     * - Elimination requires division at every step, trading the current approach's exactness on integer-valued
     *   matrices for speed.
     *
     * The 1x1, 2x2, and 3x3 cases are handled directly via closed-form formulas rather than recursing, both because
     * they're common (e.g. 3x3 minors arise from cofactor-expanding a 4x4 matrix, a common size for 3D transforms)
     * and to skip the overhead of building submatrix arrays for cases that are cheap to compute directly. The 3x3
     * formula is Sarrus' Rule, a mnemonic specific to 3x3 matrices; it does not generalize to 4x4 and up, which is
     * why cofactor expansion is still needed there.
     *
     * @param list<list<float>> $matrix Matrix data.
     * @return float Determinant of the matrix.
     */
    private function calcDet(array $matrix): float
    {
        $n = count($matrix);

        if ($n === 1) {
            return $matrix[0][0];
        }

        if ($n === 2) {
            return $matrix[0][0] * $matrix[1][1] - $matrix[0][1] * $matrix[1][0];
        }

        if ($n === 3) {
            // Sarrus' Rule.
            return $matrix[0][0] * $matrix[1][1] * $matrix[2][2]
                + $matrix[0][1] * $matrix[1][2] * $matrix[2][0]
                + $matrix[0][2] * $matrix[1][0] * $matrix[2][1]
                - $matrix[0][2] * $matrix[1][1] * $matrix[2][0]
                - $matrix[0][0] * $matrix[1][2] * $matrix[2][1]
                - $matrix[0][1] * $matrix[1][0] * $matrix[2][2];
        }

        // Cofactor expansion for larger matrices.
        $det = 0.0;
        for ($j = 0; $j < $n; $j++) {
            $submatrix = [];
            for ($i = 1; $i < $n; $i++) {
                $row = [];
                for ($k = 0; $k < $n; $k++) {
                    if ($k !== $j) {
                        $row[] = $matrix[$i][$k];
                    }
                }
                $submatrix[] = $row;
            }

            $cofactor = ($j % 2 === 0 ? 1 : -1) * $matrix[0][$j] * $this->calcDet($submatrix);
            $det += $cofactor;
        }

        return $det;
    }

    /**
     * Get the minor matrix by removing the specified row and column.
     *
     * @param int $excludeRow Row to exclude.
     * @param int $excludeColumn Column to exclude.
     * @return list<list<float>> Minor matrix.
     */
    private function getMinor(int $excludeRow, int $excludeColumn): array
    {
        $minor = [];
        for ($i = 0; $i < $this->rowCount; $i++) {
            if ($i !== $excludeRow) {
                $row = [];
                for ($j = 0; $j < $this->columnCount; $j++) {
                    if ($j !== $excludeColumn) {
                        $row[] = $this->data[$i][$j];
                    }
                }
                $minor[] = $row;
            }
        }
        return $minor;
    }

    #endregion
}
