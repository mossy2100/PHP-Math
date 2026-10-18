<?php

declare(strict_types=1);

namespace OceanMoon\Math;

use ArrayAccess;
use ArrayIterator;
use Countable;
use DomainException;
use InvalidArgumentException;
use IteratorAggregate;
use LengthException;
use LogicException;
use OceanMoon\Core\Exceptions\ArithmeticException;
use OceanMoon\Core\Floats;
use OceanMoon\Core\Numbers;
use OceanMoon\Core\Stringify;
use OceanMoon\Core\Traits\Comparison\ApproxEquatable;
use OutOfRangeException;
use OverflowException;
use Override;
use Stringable;
use Traversable;

/**
 * Encapsulates a 2-dimensional matrix and provides a number of useful methods.
 *
 * @implements ArrayAccess<int, Vector>
 * @implements IteratorAggregate<int, Vector>
 */
final class Matrix implements Stringable, Countable, ArrayAccess, IteratorAggregate
{
    use ApproxEquatable;

    #region Properties

    /**
     * The number of rows in the matrix.
     */
    private(set) int $rowCount;

    /**
     * The number of columns in the matrix.
     */
    private(set) int $columnCount;

    /**
     * The matrix data: one Vector per row.
     *
     * This must be private because even if it's private(set) if they can get $this->data they could add new elements,
     * inadvertently sizing the matrix without changing rowCount/colCount, or making it store non-Vectors or Vectors of
     * the wrong count.
     *
     * Each row Vector's object identity is established once (in the constructor or a factory method) and never
     * replaced afterward -- methods that change row content (set(), setRow()) mutate the existing Vector in place
     * rather than swapping in a new one. This matters once callers can hold a live reference to a row (see
     * offsetGet()): the reference stays valid and up to date across later mutations instead of going stale.
     *
     * @var list<Vector>
     */
    private array $data;

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

        // Build a fresh Vector per row. Not array_fill(0, $rowCount, new Vector($columnCount)): array_fill() copies
        // its value into every slot, and copying an object copies its handle, not a new instance -- every row would
        // end up sharing the very same Vector, so mutating one row would mutate them all.
        $data = [];
        for ($i = 0; $i < $rowCount; $i++) {
            $data[] = new Vector($columnCount);
        }
        $this->data = $data;
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
     * @throws InvalidArgumentException If a row isn't an array, or an element isn't an int or float.
     * @throws DomainException If the array or a row isn't a list, or an element isn't finite.
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
            // Check each row is an array.
            if (!is_array($row)) {
                throw new InvalidArgumentException("Cannot create Matrix from array. Row $i must be an array.");
            }

            // Check each row is a list.
            if (!array_is_list($row)) {
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

            // Check each row contains only finite numbers.
            foreach ($row as $j => $value) {
                // Check the value is a number.
                if (!Numbers::isNumber($value)) {
                    throw new InvalidArgumentException(
                        "Cannot create Matrix from array. Element at row $i, column $j must be an int or float."
                    );
                }

                // Check the value is finite.
                if (!is_finite($value)) {
                    throw new DomainException(Stringify::prepEx(
                        "Cannot create Matrix from array. Element at row $i, column $j (?) must be finite.",
                        $value
                    ));
                }

                // Convert the value to a float and store it in the matrix.
                $dataRow[] = (float) $value;
            }

            $data[] = Vector::fromArray($dataRow);
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

    /**
     * Deep-clone the matrix data.
     *
     * PHP's default shallow clone copies the $data array itself but not the Vector objects inside it, so without
     * this, a cloned Matrix would share its row Vectors with the original -- mutating a row of one (via set(),
     * setRow(), or offsetSet()) would silently mutate the other too, since row content is always mutated in place
     * (see the $data property doc comment). pow() relies on clone producing a fully independent Matrix.
     */
    public function __clone(): void
    {
        $this->data = array_map(static fn (Vector $v): Vector => clone $v, $this->data);
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
        return array_map(static fn (Vector $v): array => $v->toArray(), $this->data);
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
                $cell = (string) $this->data[$i]->get($j);
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

        return $this->data[$row]->get($col);
    }

    /**
     * Get a row as an independent copy.
     *
     * This is a copy: mutating the returned Vector does not affect this Matrix. For a live, mutable view of a row
     * that stays linked to this Matrix -- e.g. so `$m[$row][$col] = $x` mutates the Matrix in place -- use
     * `$m[$row]` (ArrayAccess) instead. See the "ArrayAccess Methods" section of docs/Matrix.md.
     *
     * @param int $row Row index (0-based).
     * @return Vector An independent copy of the row.
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

        return clone $this->data[$row];
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
            $column[] = $this->data[$i]->get($col);
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
                $result->set($i, $j, $this->data[$row + $i]->get($col + $j));
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
            throw new DomainException(Stringify::prepEx('Cannot set element to non-finite value: ?.', $value));
        }

        assert($row < count($this->data) && $col < $this->data[$row]->count);
        $this->data[$row]->set($col, $value);
    }

    /**
     * Set a Matrix row from a row Vector.
     *
     * Copies $v's elements into the row's existing Vector rather than replacing it, so the row's object identity
     * is preserved -- a live reference obtained via `$m[$row]` (ArrayAccess) stays valid and reflects the new
     * values, rather than going stale. $v itself is never stored by reference: later mutating the caller's own
     * $v has no effect on this Matrix.
     *
     * @param int $row Row index (0-based).
     * @param Vector $vec The row Vector.
     * @throws OutOfRangeException If row index is outside valid range.
     * @throws LengthException If the Vector has the wrong count.
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
        $vectorCount = count($vec);
        if ($vectorCount !== $this->columnCount) {
            throw new LengthException(
                "Cannot set row due to incorrect Vector count: $vectorCount. Expected {$this->columnCount}."
            );
        }

        // Copy the values into the row's existing Vector, in place.
        for ($col = 0; $col < $this->columnCount; $col++) {
            $this->data[$row]->set($col, $vec->get($col));
        }
    }

    /**
     * Set a column from a Vector.
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
        $vectorCount = count($vec);
        if ($vectorCount !== $this->rowCount) {
            throw new LengthException(
                "Cannot set column due to incorrect Vector count: $vectorCount. Expected {$this->rowCount}."
            );
        }

        // Set values.
        for ($row = 0; $row < $this->rowCount; $row++) {
            assert($row < count($this->data) && $col < $this->data[$row]->count);
            $this->data[$row]->set($col, $vec->get($row));
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
                $this->set($row + $i, $col + $j, $other->data[$i]->get($j));
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
     * @return bool True if the Matrices have the same dimensions and all elements are equal.
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

        // Check rows are equal.
        for ($i = 0; $i < $this->rowCount; $i++) {
            if (!$this->data[$i]->equal($other->data[$i])) {
                return false;
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
     * @return bool True if the Matrices have the same dimensions and all elements are approximately equal.
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

        // Check rows are approximately equal.
        for ($i = 0; $i < $this->rowCount; $i++) {
            if (!$this->data[$i]->approxEqual($other->data[$i], $relTol, $absTol)) {
                return false;
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
        $result = new self($this->rowCount, $this->columnCount);
        for ($i = 0; $i < $this->rowCount; $i++) {
            for ($j = 0; $j < $this->columnCount; $j++) {
                $result->set($i, $j, -$this->data[$i]->get($j));
            }
        }

        return $result;
    }

    /**
     * Calculate the element-wise reciprocal of this matrix.
     *
     * Not to be confused with `inv()`, the matrix inverse - this simply replaces each element with its own
     * reciprocal, with no relationship to matrix multiplication.
     *
     * @return self A new matrix with each element replaced by its reciprocal.
     * @throws ArithmeticException If any element is zero.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function reciprocal(): self
    {
        $result = new self($this->rowCount, $this->columnCount);
        for ($i = 0; $i < $this->rowCount; $i++) {
            for ($j = 0; $j < $this->columnCount; $j++) {
                $value = $this->data[$i]->get($j);
                if ($value === 0.0) {
                    throw new ArithmeticException("Cannot compute reciprocal of zero at row $i, column $j.");
                }
                $result->set($i, $j, self::checkResult(1 / $value, 'compute reciprocal of the given Matrix'));
            }
        }

        return $result;
    }

    /**
     * Calculate the inverse of this matrix.
     *
     * A matrix up to 3×3 is inverted with the adjugate (cofactor) formula, which gives exact results where the
     * arithmetic allows, e.g. the inverse of [[2, 1], [5, 3]] is exactly [[3, -1], [-5, 2]]. A larger one is inverted
     * by LU decomposition with partial pivoting, which is O(n³).
     *
     * Either way, the matrix is first checked for singularity by LU decomposition. It's singular if a pivot is
     * negligible relative to the elements of its row: no more than n × PHP_FLOAT_EPSILON times the largest of them.
     * That also rejects a matrix that's singular in exact arithmetic, but whose determinant comes out tiny rather than
     * zero in floating point, whose "inverse" would be meaningless.
     *
     * @return self New matrix representing the inverse.
     * @throws DomainException If matrix is not square.
     * @throws ArithmeticException If matrix is singular.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function inv(): self
    {
        // Check if matrix is square.
        if (!$this->isSquare()) {
            throw new DomainException('Cannot invert non-square Matrix.');
        }

        // Check the matrix isn't singular.
        $decomposition = self::luDecompose($this->toArray());
        if ($decomposition['singular']) {
            throw new ArithmeticException('Cannot invert singular Matrix.');
        }

        // Invert a matrix larger than 3×3 by LU decomposition.
        $n = $this->rowCount;
        if ($n > 3) {
            return self::invFromLu($decomposition['lu'], $decomposition['perm']);
        }

        // Invert a matrix up to 3×3 with the adjugate formula. Try without scaling first, so ordinary results are
        // unchanged, and a matrix whose elements span a wide range isn't harmed by scaling its smallest elements down
        // to 0.
        $scale = 1.0;
        $scaled = $this;
        $det = self::calcDet($this->toArray());

        // If the determinant underflowed to 0 or overflowed, as it can for a matrix of uniformly tiny or huge
        // elements, scale so the largest element is in the range [1, 2) and try again. Scaling by a power of 2 is
        // exact. inv(A) = inv(A / s) / s.
        if ($det === 0.0 || !is_finite($det)) {
            $elements = array_map(abs(...), array_merge(...$this->toArray()));
            $max = $elements === [] ? 0.0 : max($elements);
            if ($max !== 0.0) {
                $scale = 2 ** floor(log($max, 2));
                $scaled = $this->div($scale);
                $det = self::calcDet($scaled->toArray());
            }
        }

        // The determinant is finite here: either it was already, or the matrix was scaled so every element is less than
        // 2. It can still be 0 if it underflowed even after scaling, which happens when the elements span a wide range,
        // e.g. diag(1, 1e-200, 1e-200). The singularity check above has passed, so use the LU decomposition instead.
        if ($det === 0.0) {
            return self::invFromLu($decomposition['lu'], $decomposition['perm']);
        }

        // Calculate the inverse from the cofactors.
        $adjugate = new self($n, $n);

        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $minor = $scaled->getMinor($i, $j);
                $cofactor = (($i + $j) % 2 === 0 ? 1 : -1) * self::calcDet($minor);
                // Note: transposed. Normalize, as negating a zero cofactor gives -0.0.
                $element = self::checkResult($cofactor / $det / $scale, 'invert the given Matrix');
                $adjugate->set($j, $i, Floats::normalizeZero($element));
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
     * @throws OverflowException If the result is too large to represent as a float.
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
                $sum = $this->data[$i]->get($j) + $other->data[$i]->get($j);
                $result->set($i, $j, self::checkResult($sum, 'add the given Matrices'));
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
     * @throws OverflowException If the result is too large to represent as a float.
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
                $diff = $this->data[$i]->get($j) - $other->data[$i]->get($j);
                $result->set($i, $j, self::checkResult($diff, 'subtract the given Matrices'));
            }
        }
        return $result;
    }

    /**
     * Multiply this Matrix by a scalar, a Vector, or another Matrix.
     *
     * Multiplying by a Vector (_Ax_) treats the vector as a column vector; its count must equal this matrix's column
     * count. The result is a Vector. To go the other way (_xA_), call {@see Vector::mul()}.
     *
     * @param float|Vector|self $other Number, vector, or matrix to multiply by.
     * @return ($other is Vector ? Vector : self) New vector for a Vector, otherwise a new matrix.
     * @throws DomainException If $other is a float that isn't finite (±INF or NAN).
     * @throws LengthException If dimensions are incompatible for multiplication.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function mul(float|Vector|self $other): Vector|self
    {
        // Multiply matrix by a float.
        if (is_float($other)) {
            // Check the scalar is finite.
            if (!is_finite($other)) {
                throw new DomainException(Stringify::prepEx('Invalid scalar: ?. Must be finite.', $other));
            }

            // Multiply each element of the matrix by the float.
            $operation = Stringify::prepEx('multiply the given Matrix by ?', $other);
            $scaled = new self($this->rowCount, $this->columnCount);
            for ($i = 0; $i < $this->rowCount; $i++) {
                for ($j = 0; $j < $this->columnCount; $j++) {
                    $scaled->set($i, $j, self::checkResult($this->data[$i]->get($j) * $other, $operation));
                }
            }
            return $scaled;
        }

        // Multiply matrix by a vector: Ax, treating the vector as a column vector.
        if ($other instanceof Vector) {
            if ($this->columnCount !== $other->count) {
                throw new LengthException(
                    "Invalid Vector count: {$other->count}. Must equal this Matrix's column count: " .
                    "{$this->columnCount}."
                );
            }

            // Calculate each element of the result as the dot product of a row with the vector.
            $result = new Vector($this->rowCount);
            for ($i = 0; $i < $this->rowCount; $i++) {
                $sum = 0.0;
                for ($j = 0; $j < $this->columnCount; $j++) {
                    $sum += $this->data[$i]->get($j) * $other->get($j);
                }
                $result->set($i, self::checkResult($sum, 'multiply the given Matrix and Vector'));
            }

            return $result;
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
                    $sum += $this->data[$i]->get($k) * $other->data[$k]->get($j);
                }
                $result->set($i, $j, self::checkResult($sum, 'multiply the given Matrices'));
            }
        }

        return $result;
    }

    /**
     * Divide this matrix by a scalar.
     *
     * @param float $scalar Number to divide by.
     * @return self New matrix representing the quotient.
     * @throws DomainException If $scalar is not finite (±INF or NAN).
     * @throws ArithmeticException If $scalar is zero.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function div(float $scalar): self
    {
        // Check the scalar is finite. Dividing by ±INF would otherwise silently give a zero matrix.
        if (!is_finite($scalar)) {
            throw new DomainException(Stringify::prepEx('Invalid scalar: ?. Must be finite.', $scalar));
        }

        // Guard against division by zero.
        if ($scalar === 0.0) {
            throw new ArithmeticException('Cannot divide by zero.');
        }

        // Divide each element of the matrix by the scalar.
        $operation = Stringify::prepEx('divide the given Matrix by ?', $scalar);
        $scaled = new self($this->rowCount, $this->columnCount);
        for ($i = 0; $i < $this->rowCount; $i++) {
            for ($j = 0; $j < $this->columnCount; $j++) {
                $scaled->set($i, $j, self::checkResult($this->data[$i]->get($j) / $scalar, $operation));
            }
        }
        return $scaled;
    }

    /**
     * Calculate the Hadamard product (element-wise product) of this matrix with another.
     *
     * @param self $other Matrix to multiply element-wise with.
     * @return self New matrix representing the Hadamard product.
     * @throws LengthException If matrices have different dimensions.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function hadamardMul(self $other): self
    {
        // Check if dimensions are the same.
        if ($this->rowCount !== $other->rowCount || $this->columnCount !== $other->columnCount) {
            throw new LengthException(
                'Cannot compute Hadamard product with Matrix of incorrect dimensions: ' .
                "{$other->rowCount}x{$other->columnCount}. Expected {$this->rowCount}x{$this->columnCount}."
            );
        }

        // Multiply the matrices element-wise.
        $result = new self($this->rowCount, $this->columnCount);
        for ($i = 0; $i < $this->rowCount; $i++) {
            for ($j = 0; $j < $this->columnCount; $j++) {
                $product = $this->data[$i]->get($j) * $other->data[$i]->get($j);
                $result->set($i, $j, self::checkResult($product, 'multiply the given Matrices element-wise'));
            }
        }

        return $result;
    }

    /**
     * Calculate the Hadamard division (element-wise quotient) of this matrix by another.
     *
     * @param self $other Matrix to divide element-wise by.
     * @return self New matrix representing the Hadamard quotient.
     * @throws LengthException If matrices have different dimensions.
     * @throws ArithmeticException If any element of $other is zero.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function hadamardDiv(self $other): self
    {
        // Check if dimensions are the same.
        if ($this->rowCount !== $other->rowCount || $this->columnCount !== $other->columnCount) {
            throw new LengthException(
                'Cannot compute Hadamard quotient with Matrix of incorrect dimensions: ' .
                "{$other->rowCount}x{$other->columnCount}. Expected {$this->rowCount}x{$this->columnCount}."
            );
        }

        // Divide the matrices element-wise.
        $result = new self($this->rowCount, $this->columnCount);
        for ($i = 0; $i < $this->rowCount; $i++) {
            for ($j = 0; $j < $this->columnCount; $j++) {
                $divisor = $other->data[$i]->get($j);
                if ($divisor === 0.0) {
                    throw new ArithmeticException("Cannot divide by zero at row $i, column $j.");
                }
                $quotient = $this->data[$i]->get($j) / $divisor;
                $result->set($i, $j, self::checkResult($quotient, 'divide the given Matrices element-wise'));
            }
        }

        return $result;
    }

    #endregion

    #region Power methods

    /**
     * Raise this matrix to a power.
     *
     * Uses exponentiation by squaring. For a negative exponent, the base is inverted before squaring rather than
     * squaring the base and inverting the result at the end, so intermediate values shrink toward the (typically
     * small) final answer instead of risking an unnecessary overflow to INF on the way there.
     *
     * @param int $exp Power to raise to.
     * @return self New matrix representing the result.
     * @throws DomainException If matrix is not square.
     * @throws ArithmeticException If the exponent is negative and the matrix is singular.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function pow(int $exp): self
    {
        // Check if matrix is square.
        if (!$this->isSquare()) {
            throw new DomainException('Cannot raise non-square Matrix to a power.');
        }

        // Handle exponent = 0.
        if ($exp === 0) {
            return self::identity($this->rowCount);
        }

        // Handle exponent = 1. Any number to power 1 is itself.
        if ($exp === 1) {
            return clone $this;
        }

        try {
            return $this->calcPow($exp);
        } catch (OverflowException $e) {
            throw new OverflowException(
                'Cannot raise the given Matrix to the given power. The result would overflow.',
                0,
                $e
            );
        }
    }

    /**
     * Square this matrix.
     *
     * Equivalent to pow(2), but more readable as a standalone call.
     *
     * @return self A new matrix representing the square of this matrix.
     * @throws DomainException If the matrix is not square.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function sqr(): self
    {
        if (!$this->isSquare()) {
            throw new DomainException('Cannot square non-square Matrix.');
        }

        try {
            return $this->mul($this);
        } catch (OverflowException $e) {
            throw new OverflowException('Cannot square the given Matrix. The result would overflow.', 0, $e);
        }
    }

    #endregion

    #region Linear algebra methods

    /**
     * Get the transpose of this matrix.
     *
     * @return self New matrix representing the transpose.
     */
    public function t(): self
    {
        $result = new self($this->columnCount, $this->rowCount);
        for ($i = 0; $i < $this->rowCount; $i++) {
            for ($j = 0; $j < $this->columnCount; $j++) {
                $result->set($j, $i, $this->data[$i]->get($j));
            }
        }

        return $result;
    }

    /**
     * Calculate the determinant of this matrix.
     *
     * @return float The determinant.
     * @throws DomainException If matrix is not square.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function det(): float
    {
        // Check if matrix is square.
        if (!$this->isSquare()) {
            throw new DomainException('Cannot compute determinant of non-square Matrix.');
        }

        return self::checkResult(self::calcDet($this->toArray()), 'compute determinant of the given Matrix');
    }

    /**
     * Calculate the trace of this matrix (sum of diagonal elements).
     *
     * @return float The trace.
     * @throws DomainException If matrix is not square.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function trace(): float
    {
        if (!$this->isSquare()) {
            throw new DomainException('Cannot compute trace of non-square Matrix.');
        }

        $sum = 0.0;
        for ($i = 0; $i < $this->rowCount; $i++) {
            $sum += $this->data[$i]->get($i);
        }

        return self::checkResult($sum, 'compute trace of the given Matrix');
    }

    #endregion

    #region Norm methods

    /**
     * Calculate the Frobenius norm (square root of the sum of all squared elements).
     *
     * This is the matrix analogue of the Euclidean norm for vectors: the magnitude of all the elements, taken as one
     * vector. So, like Vector's magnitude, it doesn't overflow or underflow when squaring the elements would.
     *
     * @return float The Frobenius norm.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function norm(): float
    {
        try {
            return Vector::fromArray(array_merge(...$this->toArray()))->magnitude;
        } catch (OverflowException $e) {
            throw new OverflowException('Cannot compute norm of the given Matrix. The result would overflow.', 0, $e);
        }
    }

    /**
     * Calculate the P1 norm (maximum absolute column sum).
     *
     * @return float The P1 norm.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function p1Norm(): float
    {
        $max = 0.0;
        for ($j = 0; $j < $this->columnCount; $j++) {
            $colSum = 0.0;
            for ($i = 0; $i < $this->rowCount; $i++) {
                $colSum += abs($this->data[$i]->get($j));
            }
            $max = max($max, $colSum);
        }

        return self::checkResult($max, 'compute P1 norm of the given Matrix');
    }

    /**
     * Calculate the P-infinity norm (maximum absolute row sum).
     *
     * @return float The P-infinity norm.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    public function pInfNorm(): float
    {
        $max = 0.0;
        for ($i = 0; $i < $this->rowCount; $i++) {
            $rowSum = 0.0;
            for ($j = 0; $j < $this->columnCount; $j++) {
                $rowSum += abs($this->data[$i]->get($j));
            }
            $max = max($max, $rowSum);
        }

        return self::checkResult($max, 'compute P-infinity norm of the given Matrix');
    }

    #endregion

    #region Aggregation methods

    /**
     * Get the number of rows in the Matrix.
     *
     * This matches the row indexes used by `$m[$i]` and foreach. For the number of elements, use
     * `$m->rowCount * $m->columnCount`.
     *
     * @return int The number of rows.
     */
    #[Override] // Countable
    public function count(): int
    {
        return $this->rowCount;
    }

    #endregion

    #region ArrayAccess methods

    /**
     * Check if a row offset exists.
     *
     * @param mixed $offset Row index to check.
     * @return bool
     */
    #[Override] // ArrayAccess
    public function offsetExists(mixed $offset): bool
    {
        return is_int($offset) && $offset >= 0 && $offset < $this->rowCount;
    }

    /**
     * Get a row at an offset.
     *
     * Unlike `getRow()`, this returns the Matrix's actual internal Vector for that row, not an independent copy --
     * mutating the returned Vector mutates this Matrix. This is what makes `$m[$row][$col] = $x` work: the engine
     * calls this method to get the row, then calls `offsetSet()` on that same Vector object. See the "ArrayAccess
     * Methods" section of docs/Matrix.md.
     *
     * @param mixed $offset Row index to get.
     * @return Vector The live row Vector.
     * @throws InvalidArgumentException If the offset is not an int.
     * @throws OutOfRangeException If the offset is outside the valid range.
     */
    #[Override] // ArrayAccess
    public function offsetGet(mixed $offset): Vector
    {
        // Check index type.
        if (!is_int($offset)) {
            throw new InvalidArgumentException('Invalid index type: ' . get_debug_type($offset) . '. Must be int.');
        }

        // Check if row index is within bounds.
        if ($offset < 0 || $offset >= $this->rowCount) {
            throw new OutOfRangeException(
                "Invalid row index: $offset. Must be in the range 0-" . ($this->rowCount - 1) . '.'
            );
        }

        return $this->data[$offset];
    }

    /**
     * Set a row at an offset.
     *
     * Equivalent to `setRow()`: the given Vector's elements are copied into the row's existing Vector, which is
     * never replaced (see the $data property doc comment).
     *
     * @param mixed $offset Row index to set.
     * @param mixed $value The row Vector.
     * @throws InvalidArgumentException If the offset is not an int, or the value is not a Vector.
     * @throws OutOfRangeException If offset is outside valid range.
     * @throws LengthException If the Vector has the wrong number of elements.
     */
    #[Override] // ArrayAccess
    public function offsetSet(mixed $offset, mixed $value): void
    {
        // Check index type.
        if (!is_int($offset)) {
            throw new InvalidArgumentException('Invalid index type: ' . get_debug_type($offset) . '. Must be int.');
        }

        // Check value type.
        if (!$value instanceof Vector) {
            throw new InvalidArgumentException(
                'Invalid row type: ' . get_debug_type($value) . '. Must be Vector.'
            );
        }

        $this->setRow($offset, $value);
    }

    /**
     * Unset is not supported for Matrix, which has a fixed number of rows.
     *
     * @param mixed $offset Row index.
     * @throws LogicException Always throws.
     */
    #[Override] // ArrayAccess
    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Cannot unset Matrix rows.');
    }

    #endregion

    #region IteratorAggregate implementation

    /**
     * Get an iterator over the rows, for foreach loops.
     *
     * Like offsetGet(), it yields the Matrix's actual row Vectors, so changing a row in the loop changes this Matrix.
     *
     * @return Traversable<int, Vector> The iterator.
     */
    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }

    #endregion

    #region Helper methods

    /**
     * Calculate a power of this square matrix, for pow(), once the exponent 0 and 1 cases are handled.
     *
     * @param int $exp Power to raise to.
     * @return self The result.
     * @throws ArithmeticException If the exponent is negative and the matrix is singular.
     * @throws OverflowException If the result, or an intermediate value, is too large to represent as a float.
     */
    private function calcPow(int $exp): self
    {
        // Handle exponent = 2. Multiply directly rather than delegating to sqr(), whose isSquare() guard would be
        // redundant here (already checked above).
        if ($exp === 2) {
            return $this->mul($this);
        }

        // Handle exponent = -1. Delegate to inv().
        if ($exp === -1) {
            return $this->inv();
        }

        // Handle exponent = PHP_INT_MIN.
        if ($exp === PHP_INT_MIN) {
            $inv = $this->inv();
            return $inv->calcPow(PHP_INT_MAX)->mul($inv);
        }

        // For other exponents, do exponentiation by squaring.
        $result = self::identity($this->rowCount);
        $x = clone $this;
        // For negative exponents, invert the base.
        if ($exp < 0) {
            $x = $x->inv();
        }
        $y = abs($exp);
        while ($y > 0) {
            if ($y % 2 === 1) {
                $result = $result->mul($x);
            }
            // Halve the exponent.
            $y = intdiv($y, 2);
            if ($y > 0) {
                // Square the base.
                $x = $x->mul($x);
            }
        }

        return $result;
    }

    /**
     * Check a calculated value is representable.
     *
     * With finite operands, a non-finite result means it overflowed. A NAN result means an intermediate value
     * overflowed (e.g. INF - INF or 0 × INF), so it's reported as an overflow too.
     *
     * @param float $value The calculated value.
     * @param string $operation The operation, for the exception message, e.g. 'add the given Matrices'.
     * @return float The value.
     * @throws OverflowException If the value is not finite.
     */
    private static function checkResult(float $value, string $operation): float
    {
        if (!is_finite($value)) {
            throw new OverflowException("Cannot $operation. The result would overflow.");
        }

        return $value;
    }

    /**
     * Calculate the determinant of a square matrix.
     *
     * Matrices up to 3×3 use closed-form formulas, which give exact results where the arithmetic allows, e.g. 0 for
     * [[1, 2, 3], [4, 5, 6], [7, 8, 9]]. The 3×3 formula is Sarrus' Rule. Larger matrices use LU decomposition, which
     * is O(n³): the determinant is the product of the pivots, negated for an odd number of row swaps. A singular
     * matrix that size can give a tiny non-zero result rather than exactly zero, due to rounding.
     *
     * The result may be non-finite. The caller checks it.
     *
     * @param list<list<float>> $matrix Matrix data.
     * @return float Determinant of the matrix.
     */
    private static function calcDet(array $matrix): float
    {
        $n = count($matrix);

        if ($n === 0) {
            // Determinant of the 0x0 matrix is the empty product, 1, so the identity and multiplicativity
            // properties hold for empty matrices.
            return 1.0;
        }

        if ($n === 1) {
            // Determinant of a 1x1 matrix is its single element.
            return $matrix[0][0];
        }

        if ($n === 2) {
            // Product of primary diagonal minus product of secondary diagonal.
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

        // LU decomposition for matrix sizes 4x4 and greater.
        $decomposition = self::luDecompose($matrix);
        $det = (float) $decomposition['sign'];
        for ($i = 0; $i < $n; $i++) {
            $det *= $decomposition['lu'][$i][$i];
        }

        return $det;
    }

    /**
     * Decompose a square matrix into PA = LU by Gaussian elimination with partial pivoting.
     *
     * L is unit lower triangular and U is upper triangular. Both are returned packed into one array: U on and above
     * the diagonal, and L's multipliers below it. Each step swaps in the row with the largest pivot candidate, which
     * keeps every multiplier's magnitude at most 1.
     *
     * The matrix is reported as singular if a pivot is negligible relative to the elements of its original row: no
     * more than n × PHP_FLOAT_EPSILON times the largest of them. Comparing with each row's own scale, rather than the
     * whole matrix's, means a matrix whose rows differ widely in scale, e.g. [[1e-300, 0], [0, 1e300]], isn't
     * mistaken for a singular one.
     *
     * @param list<list<float>> $matrix Matrix data.
     * @return array{lu: array<int, array<int, float>>, perm: array<int, int>, sign: int, singular: bool} The packed
     * LU, the row permutation (position to original row), the permutation's sign (1 or -1), and whether the matrix is
     * singular.
     */
    private static function luDecompose(array $matrix): array
    {
        $n = count($matrix);
        $lu = $matrix;
        $perm = range(0, $n - 1);
        $sign = 1;
        $singular = false;

        // Get the scale of each original row: its largest absolute element.
        $rowScales = [];
        foreach ($matrix as $row) {
            $rowScale = 0.0;
            foreach ($row as $value) {
                $rowScale = max($rowScale, abs($value));
            }
            $rowScales[] = $rowScale;
        }

        for ($k = 0; $k < $n; $k++) {
            // Find the row with the largest pivot candidate.
            $pivotRow = $k;
            $max = abs($lu[$k][$k]);
            for ($i = $k + 1; $i < $n; $i++) {
                if (abs($lu[$i][$k]) > $max) {
                    $max = abs($lu[$i][$k]);
                    $pivotRow = $i;
                }
            }

            // Swap it into place.
            if ($pivotRow !== $k) {
                $row = $lu[$k];
                $lu[$k] = $lu[$pivotRow];
                $lu[$pivotRow] = $row;
                $index = $perm[$k];
                $perm[$k] = $perm[$pivotRow];
                $perm[$pivotRow] = $index;
                $sign = -$sign;
            }

            // Check for a negligible pivot.
            $pivot = $lu[$k][$k];
            if (abs($pivot) <= $n * PHP_FLOAT_EPSILON * $rowScales[$perm[$k]]) {
                $singular = true;
            }

            // A zero pivot means the rest of the column is zero too, so there's nothing to eliminate.
            if ($pivot === 0.0) {
                continue;
            }

            // Eliminate the column below the pivot.
            for ($i = $k + 1; $i < $n; $i++) {
                $multiplier = $lu[$i][$k] / $pivot;
                $lu[$i][$k] = $multiplier;
                for ($j = $k + 1; $j < $n; $j++) {
                    $lu[$i][$j] -= $multiplier * $lu[$k][$j];
                }
            }
        }

        return [
            'lu'       => $lu,
            'perm'     => $perm,
            'sign'     => $sign,
            'singular' => $singular,
        ];
    }

    /**
     * Calculate the inverse of a non-singular matrix from its LU decomposition.
     *
     * Each column of the inverse solves Ax = e (a column of the identity): first Ly = Pe by forward substitution, then
     * Ux = y by back substitution.
     *
     * @param array<int, array<int, float>> $lu The packed LU, from luDecompose().
     * @param array<int, int> $perm The row permutation, from luDecompose().
     * @return self The inverse.
     * @throws OverflowException If the result is too large to represent as a float.
     */
    private static function invFromLu(array $lu, array $perm): self
    {
        $n = count($lu);
        $inverse = new self($n, $n);

        for ($col = 0; $col < $n; $col++) {
            // Solve Ly = Pe, where e is this column of the identity.
            $y = [];
            for ($i = 0; $i < $n; $i++) {
                $sum = $perm[$i] === $col ? 1.0 : 0.0;
                for ($j = 0; $j < $i; $j++) {
                    $sum -= $lu[$i][$j] * $y[$j];
                }
                $y[$i] = $sum;
            }

            // Solve Ux = y.
            $x = [];
            for ($i = $n - 1; $i >= 0; $i--) {
                $sum = $y[$i];
                for ($j = $i + 1; $j < $n; $j++) {
                    $sum -= $lu[$i][$j] * $x[$j];
                }
                $x[$i] = $sum / $lu[$i][$i];
            }

            // Store the column. Normalize, as the substitutions can give -0.0.
            for ($i = 0; $i < $n; $i++) {
                $inverse->set($i, $col, Floats::normalizeZero(self::checkResult($x[$i], 'invert the given Matrix')));
            }
        }

        return $inverse;
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
                        $row[] = $this->data[$i]->get($j);
                    }
                }
                $minor[] = $row;
            }
        }
        return $minor;
    }

    #endregion
}
