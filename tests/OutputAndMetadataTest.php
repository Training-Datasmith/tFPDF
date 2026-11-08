<?php

class OutputAndMetadataTest extends TfpdfTestCase
{
    public function testOutputStringLeavesNoPrintedBody()
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, 's');
        $bytes = $pdf->Output('S');
        PdfAssert::assertPdf($bytes);
        $this->assertTrue(is_string($bytes));
    }

    public function testOutputWritesFileDestination()
    {
        $path = sys_get_temp_dir() . '/tfpdf-out-' . uniqid('', true) . '.pdf';
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, 'file');
        $pdf->Output($path, 'F');
        $this->assertFileExists($path);
        $bytes = file_get_contents($path);
        $this->assertTrue($bytes !== false);
        PdfAssert::assertPdf($bytes);
        @unlink($path);
    }

    public function testOutputCliInlineMode()
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, 'inline');
        ob_start();
        $pdf->Output('', 'I');
        $body = ob_get_clean();
        PdfAssert::assertPdf($body);
    }

    public function testMetadataUtf8Title()
    {
        $pdf = new tFPDF();
        $pdf->SetTitle('Γειά', true);
        $pdf->AddPage();
        $bytes = $pdf->Output('S');
        $this->assertContainsString('/Title', $bytes);
        $this->assertContainsString("\xFE\xFF", $bytes);
    }

    public function testCreationDateUtcOffset()
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $bytes = $pdf->Output('S');
        $this->assertRegExp(
            '/\/CreationDate \(D:\d{14}\+00\'00\'\)/',
            $bytes
        );
    }

    public function testDefaultCompressionFlatesPageStreams()
    {
        $bytes = DocumentBuilder::coreOnePager('flate');
        $this->assertContainsString('/Filter /FlateDecode', $bytes);
        PdfAssert::assertContainsInStreams($bytes, '(flate) Tj');
    }

    public function testCompressionDisabledExposesOperatorsInObject()
    {
        $pdf = new tFPDF();
        $pdf->SetCompression(false);
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, 'plain');
        $bytes = $pdf->Output('S');
        PdfAssert::assertPdf($bytes);
        $this->assertContainsString('(plain) Tj', $bytes);
    }
}
