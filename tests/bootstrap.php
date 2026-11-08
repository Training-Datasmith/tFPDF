<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/PdfAssert.php';
require __DIR__ . '/TfpdfTestCase.php';
require __DIR__ . '/DocumentBuilder.php';
require __DIR__ . '/NamespacedDocument.php';

date_default_timezone_set('UTC');
error_reporting(E_ALL);

if (!extension_loaded('mbstring')) {
    fwrite(STDERR, "ext-mbstring is required to run the test suite.\n");
    exit(1);
}

if (!extension_loaded('zlib')) {
    fwrite(STDERR, "zlib is required to run the test suite.\n");
    exit(1);
}

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    $fail = [
        E_WARNING,
        E_NOTICE,
        E_USER_WARNING,
        E_USER_NOTICE,
        E_DEPRECATED,
        E_USER_DEPRECATED,
    ];
    if (in_array($severity, $fail, true)) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    return false;
});
