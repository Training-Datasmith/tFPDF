<?php

declare(strict_types=1);

final class CoreFontTest extends TfpdfTestCase
{
    public function testHelveticaStringWidthForCapitalA(): void
    {
        $pdf = new tFPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $width = $pdf->GetStringWidth('A');
        $this->assertEqualsWithDelta(2.824, $width, 0.02);
        $this->assertSame(0.0, $pdf->GetStringWidth(''));
    }

    public function testArialMapsToHelveticaInPdf(): void
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, 'arial');
        $bytes = $pdf->Output('S');
        $this->assertStringContainsString('/BaseFont /Helvetica', $bytes);
    }

    public function testCoreFontOperatorsInStream(): void
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, 'Hello');
        $bytes = $pdf->Output('S');
        PdfAssert::assertContainsInStreams($bytes, '12.00 Tf');
        PdfAssert::assertContainsInStreams($bytes, '(Hello) Tj');
    }

    public function testUnderlineStyleAddsRectangleFill(): void
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', 'U', 12);
        $pdf->Cell(0, 10, 'u');
        $bytes = $pdf->Output('S');
        PdfAssert::assertContainsInStreams($bytes, 're f');
    }

    public function testSetFontSizeUpdatesStream(): void
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, 'a');
        $pdf->SetFontSize(18);
        $pdf->Cell(0, 10, 'b');
        $bytes = $pdf->Output('S');
        PdfAssert::assertContainsInStreams($bytes, '18.00 Tf');
    }

    public function testAddFontCoreIsIdempotent(): void
    {
        $pdf = new tFPDF();
        $pdf->AddFont('helvetica', '', 'helvetica.php', false);
        $pdf->AddFont('helvetica', '', 'helvetica.php', false);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);
        $this->addToAssertionCount(1);
    }
}
