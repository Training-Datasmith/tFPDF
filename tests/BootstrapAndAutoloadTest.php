<?php

declare(strict_types=1);

final class BootstrapAndAutoloadTest extends TfpdfTestCase
{
    public function testClassesAutoloadViaComposer(): void
    {
        $this->assertTrue(class_exists('tFPDF'));
        $this->assertTrue(class_exists('TTFontFile'));
    }

    public function testVersionConstant(): void
    {
        $this->assertSame('1.33', tFPDF::VERSION);
    }

    public function testConstructsWithMbstringAvailable(): void
    {
        $pdf = new tFPDF();
        $this->assertInstanceOf(tFPDF::class, $pdf);
    }

    public function testProducerInInfoDictionary(): void
    {
        $bytes = DocumentBuilder::coreOnePager();
        PdfAssert::assertPdf($bytes);
        $this->assertStringContainsString('(tFPDF 1.33)', $bytes);
    }
}
