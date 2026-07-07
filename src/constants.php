<?php

/**
 * @file constants.php
 * Contains mathematical constants used in the Math package.
 */

declare(strict_types=1);

namespace OceanMoon\Math;

/**
 * The imaginary unit, represented as a Complex number with real part 0 and imaginary part 1.
 * This is a useful abbreviation for Complex::i().
 * To use it without requiring the namespace every time, include the following line:
 * `use const OceanMoon\Math\I;`
 */
const I = new Complex(0, 1); // @codeCoverageIgnore
