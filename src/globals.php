<?php

/**
 * @file globals.php
 * Global constants for the Math package.
 */

declare(strict_types=1);

namespace OceanMoon\Math;

/**
 * The imaginary unit 'i', represented as a Complex with real part 0 and imaginary part 1.
 *
 * To use it without requiring the namespace every time, include the following line:
 * ```php
 * use const OceanMoon\Math\M_I;
 * ```
 */
const M_I = new Complex(0, 1);
