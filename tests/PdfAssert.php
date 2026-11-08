<?php

declare(strict_types=1);

use PHPUnit\Framework\Assert;

final class PdfAssert
{
    public static function assertPdf(string $bytes): void
    {
        Assert::assertStringStartsWith('%PDF-', $bytes, 'PDF header');
        Assert::assertStringContainsString('xref', $bytes);
        Assert::assertStringContainsString('startxref', $bytes);
        Assert::assertStringEndsWith("%%EOF\n", $bytes, 'PDF EOF');
        Assert::assertStringContainsString('(tFPDF 1.33)', $bytes, 'Producer metadata');
    }

    public static function pageCount(string $bytes): int
    {
        if (preg_match('/\/Type \/Pages[\s\S]*?\/Count (\d+)/', $bytes, $m)) {
            return (int) $m[1];
        }

        throw new RuntimeException('Could not parse page count from PDF');
    }

    public static function normalize(string $bytes): string
    {
        return preg_replace('/\/CreationDate \(D:[^)]+\)/', '/CreationDate (D:NORMALIZED)', $bytes) ?? $bytes;
    }

    public static function inflateStreams(string $bytes): string
    {
        $decoded = [];
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
            if (str_contains($header, '/FlateDecode')) {
                $plain = @gzuncompress($payload);
                if ($plain !== false) {
                    $decoded[] = $plain;
                }
            }
            $pos = $end + 9;
        }

        return implode("\n", $decoded);
    }

    public static function utf16BeLiteralsToUtf8(string $content): string
    {
        $out = '';
        if (!preg_match_all('/\((?:\\\\.|[^\\\\)])*\)/', $content, $matches)) {
            return $content;
        }
        foreach ($matches[0] as $match) {
            $inner = substr($match, 1, -1);
            $inner = stripcslashes($inner);
            if (str_starts_with($inner, "\xFE\xFF")) {
                $utf8 = mb_convert_encoding(substr($inner, 2), 'UTF-8', 'UTF-16BE');
                $out .= $utf8 . "\n";
            }
        }

        return $out;
    }

    public static function assertContainsInStreams(string $pdf, string $needle): void
    {
        $streams = self::inflateStreams($pdf);
        Assert::assertStringContainsString($needle, $streams, 'Expected content in inflated streams');
    }
}
