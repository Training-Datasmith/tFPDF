<?php

class TfpdfTestCase extends PHPUnit_Framework_TestCase
{
    protected function tearDown()
    {
        $patterns = array(
            dirname(__DIR__) . '/font/unifont/*.mtx.php',
            dirname(__DIR__) . '/font/unifont/*.cw.dat',
            dirname(__DIR__) . '/font/unifont/*.cw127.php',
        );
        foreach ($patterns as $pattern) {
            foreach (glob($pattern) ?: array() as $file) {
                @unlink($file);
            }
        }
        parent::tearDown();
    }

    protected function repoPath($relative = '')
    {
        $root = dirname(__DIR__);

        return $relative === '' ? $root : $root . '/' . ltrim($relative, '/');
    }

    protected function assertContainsString($needle, $haystack, $message = '')
    {
        $this->assertContains($needle, $haystack, $message);
    }

    protected function assertNotContainsString($needle, $haystack, $message = '')
    {
        $this->assertNotContains($needle, $haystack, $message);
    }
}
