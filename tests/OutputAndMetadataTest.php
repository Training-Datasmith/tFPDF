<?php

declare(strict_types=1);

final class OutputAndMetadataTest extends TfpdfTestCase
{
    public function testOutputStringLeavesNoPrintedBody(): void
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, 's');
        $bytes = $pdf->Output('S');
        PdfAssert::assertPdf($bytes);
        $this->assertIsString($bytes);
    }

    public function testOutputWritesFileDestination(): void
    {
        $path = sys_get_temp_dir() . '/tfpdf-out-' . uniqid('', true) . '.pdf';
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, 'file');
        $pdf->Output($path, 'F');
        $this->assertFileExists($path);
        $bytes = file_get_contents($path);
        $this->assertNotFalse($bytes);
        PdfAssert::assertPdf($bytes);
        @unlink($path);
    }

    public function testOutputCliInlineMode(): void
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

    public function testMetadataUtf8Title(): void
    {
        $pdf = new tFPDF();
        $pdf->SetTitle('Γειά', true);
        $pdf->AddPage();
        $bytes = $pdf->Output('S');
        $this->assertStringContainsString('/Title', $bytes);
        $this->assertStringContainsString("\xFE\xFF", $bytes);
    }

    public function testCreationDateUtcOffset(): void
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $bytes = $pdf->Output('S');
        $this->assertMatchesRegularExpression(
            '/\/CreationDate \(D:\d{14}\+00\'00\'\)/',
            $bytes
        );
    }

    public function testDefaultCompressionFlatesPageStreams(): void
    {
        $bytes = DocumentBuilder::coreOnePager('flate');
        $this->assertStringContainsString('/Filter /FlateDecode', $bytes);
        PdfAssert::assertContainsInStreams($bytes, '(flate) Tj');
    }

    public function testCompressionDisabledExposesOperatorsInObject(): void
    {
        $pdf = new tFPDF();
        $pdf->SetCompression(false);
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, 'plain');
        $bytes = $pdf->Output('S');
        PdfAssert::assertPdf($bytes);
        $this->assertStringContainsString('(plain) Tj', $bytes);
    }
}
