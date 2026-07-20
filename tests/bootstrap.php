<?php

declare(strict_types=1);

// Enable class autoloading for the Math package.
require_once __DIR__ . '/../vendor/autoload.php';

// Define constant for floating-point comparison tolerance.
require_once __DIR__ . '/epsilon.php';

// If the oceanmoon_math extension is loaded (e.g. enabled globally on this machine for other
// projects), it registers its own native Complex/Rational/Vector/Matrix classes before
// autoloading ever runs -- silently shadowing this package's own src/ classes and defeating the
// point of this test suite (verifying the pure-PHP implementation works standalone). Composer's
// "test"/"analyze" scripts already guard against this (PHP_INI_SCAN_DIR=, see composer.json), but
// print a visible status line either way so it's obvious at a glance which classes are actually
// under test, including when phpunit is invoked directly rather than via composer.
if (extension_loaded('oceanmoon_math')) {
    fwrite(
        STDERR,
        "\n\033[41;97m oceanmoon_math extension is LOADED -- testing the C extension's classes, not this package's! " .
        "\033[0m\n\n"
    );
} else {
    fwrite(STDERR, "\n\033[42;30m oceanmoon_math extension: not loaded (testing pure PHP). \033[0m\n\n");
}
