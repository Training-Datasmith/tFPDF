<?php

class BootstrapAndAutoloadTest extends TfpdfTestCase
{
    public function testClassesAutoloadViaComposer()
    {
        $this->assertTrue(class_exists('tFPDF'));
        $this->assertTrue(class_exists('TTFontFile'));
    }

    public function testVersionConstant()
    {
        $this->assertSame('1.33', tFPDF::VERSION);
    }

    public function testConstructsWithMbstringAvailable()
    {
        $pdf = new tFPDF();
        $this->assertInstanceOf('tFPDF', $pdf);
    }

    public function testProducerInInfoDictionary()
    {
        $bytes = DocumentBuilder::coreOnePager();
        PdfAssert::assertPdf($bytes);
        $this->assertContainsString('(tFPDF 1.33)', $bytes);
    }
}
