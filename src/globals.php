<?php

/**
 * @file globals.php
 * Global constants and maybe other things for the Math package.
 */

declare(strict_types=1);

namespace Mossy2100\Math;

/**
 * The imaginary unit 'i', represented as a Complex with real part 0 and imaginary part 1.
 *
 * To use it in a script without having to provide the namespace every time, include the following line:
 * ```php
 * use const Mossy2100\Math\M_I;
 * ```
 */
const M_I = new Complex(0, 1);
