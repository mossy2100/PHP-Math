<?php

/**
 * @file constants.php
 * Contains mathematical constants used by the Math package.
 */

declare(strict_types=1);

namespace OceanMoon\Math;

// @codeCoverageIgnoreStart

// If the complex extension is loaded, it already registers this constant itself (at RINIT, so it's request-scoped
// rather than shared across requests -- see extensions/complex/README.md). const declarations can't be conditional
// (they must be unconditional, top-level statements), so the only way to skip redeclaring it is to bail out of the
// whole file before reaching that line.
if (defined(__NAMESPACE__ . '\\I')) {
    return;
}

/**
 * The imaginary unit, represented as a Complex number with real part 0 and imaginary part 1.
 * To use it without requiring the namespace every time, include the following line:
 * ```php
 * use const OceanMoon\Math\I;
 * ```
 */
const I = new Complex(0, 1);

// @codeCoverageIgnoreEnd
