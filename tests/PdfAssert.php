<?php

class PdfAssert
{
    public static function assertPdf($bytes)
    {
        PHPUnit_Framework_Assert::assertStringStartsWith('%PDF-', $bytes, 'PDF header');
        PHPUnit_Framework_Assert::assertContains('xref', $bytes);
        PHPUnit_Framework_Assert::assertContains('startxref', $bytes);
        PHPUnit_Framework_Assert::assertStringEndsWith("%%EOF\n", $bytes, 'PDF EOF');
        PHPUnit_Framework_Assert::assertContains('(tFPDF 1.33)', $bytes, 'Producer metadata');
    }

    public static function pageCount($bytes)
    {
        if (preg_match('/\/Type \/Pages[\s\S]*?\/Count (\d+)/', $bytes, $m)) {
            return (int) $m[1];
        }

        throw new RuntimeException('Could not parse page count from PDF');
    }

    public static function normalize($bytes)
    {
        $normalized = preg_replace('/\/CreationDate \(D:[^)]+\)/', '/CreationDate (D:NORMALIZED)', $bytes);
        if ($normalized === null) {
            return $bytes;
        }

        return $normalized;
    }

    public static function inflateStreams($bytes)
    {
        $decoded = array();
        $pos = 0;
        $len = strlen($bytes);

        while (($idx = strpos($bytes, 'stream', $pos)) !== false) {
            $headerStart = max(0, $idx - 400);
            $header = substr($bytes, $headerStart, $idx - $headerStart);
            $dataStart = $idx + 6;
            if ($dataStart < $len && $bytes[$dataStart] === "\r") {
                ++$dataStart;
            }
            if ($dataStart < $len && $bytes[$dataStart] === "\n") {
                ++$dataStart;
            }
            $end = strpos($bytes, 'endstream', $dataStart);
            if ($end === false) {
                break;
            }
            $payload = substr($bytes, $dataStart, $end - $dataStart);
            if (strpos($header, '/FlateDecode') !== false) {
                $plain = @gzuncompress($payload);
                if ($plain !== false) {
                    $decoded[] = $plain;
                }
            }
            $pos = $end + 9;
        }

        return implode("\n", $decoded);
    }

    public static function assertContainsInStreams($pdf, $needle)
    {
        $streams = self::inflateStreams($pdf);
        PHPUnit_Framework_Assert::assertContains(
            $needle,
            $streams,
            'Expected content in inflated streams'
        );
    }
}
