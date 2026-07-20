<?php

/**
 * @file bootstrap.php
 * Conditionally loads globals.php depending on whether the Math extension is loaded.
 */

declare(strict_types=1);

namespace OceanMoon\Math;

// This if block is provided to allow for the case where the Math package and Math extension are both loaded at the same
// time, however unlikely this is.
// The current file (bootstrap.php) is loaded by the Math package. But if the Math extension is loaded, it already
// registers the M_I constant itself. Redeclaring it causes an error.
// Note: 'oceanmoon_math' is the extension's actual registered module name (see
// extensions/Math/config.m4's PHP_NEW_EXTENSION(oceanmoon_math, ...) call) -- not
// 'oceanmoon/math-ext', which is only its Composer package name.
if (!extension_loaded('oceanmoon_math')) {
    // We need to use require_once because const isn't allowed inside an if block.
    require_once __DIR__ . '/globals.php';
}
