<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

abstract class TfpdfTestCase extends TestCase
{
    protected function tearDown(): void
    {
        $patterns = [
            dirname(__DIR__) . '/font/unifont/*.mtx.php',
            dirname(__DIR__) . '/font/unifont/*.cw.dat',
            dirname(__DIR__) . '/font/unifont/*.cw127.php',
        ];
        foreach ($patterns as $pattern) {
            foreach (glob($pattern) ?: [] as $file) {
                @unlink($file);
            }
        }
        parent::tearDown();
    }

    protected function repoPath(string $relative = ''): string
    {
        $root = dirname(__DIR__);

        return $relative === '' ? $root : $root . '/' . ltrim($relative, '/');
    }
}
