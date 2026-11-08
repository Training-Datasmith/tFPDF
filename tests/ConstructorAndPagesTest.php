<?php

declare(strict_types=1);

final class ConstructorAndPagesTest extends TfpdfTestCase
{
    public function testA4PortraitDimensionsInMillimetres(): void
    {
        $pdf = new tFPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $this->assertEqualsWithDelta(210.0, $pdf->GetPageWidth(), 0.02);
        $this->assertEqualsWithDelta(297.0, $pdf->GetPageHeight(), 0.02);
    }

    public function testA4LandscapeSwapsDimensions(): void
    {
        $pdf = new tFPDF('L', 'mm', 'A4');
        $pdf->AddPage();
        $this->assertEqualsWithDelta(297.0, $pdf->GetPageWidth(), 0.02);
    }

    public function testCustomSizeNormalizesShorterSideFirstForPortrait(): void
    {
        $pdf = new tFPDF('P', 'mm', [200, 100]);
        $pdf->AddPage();
        $this->assertEqualsWithDelta(100.0, $pdf->GetPageWidth(), 0.01);
        $this->assertEqualsWithDelta(200.0, $pdf->GetPageHeight(), 0.01);
    }

    public function testCustomSizeLandscape(): void
    {
        $pdf = new tFPDF('L', 'mm', [200, 100]);
        $pdf->AddPage();
        $this->assertEqualsWithDelta(200.0, $pdf->GetPageWidth(), 0.01);
        $this->assertEqualsWithDelta(100.0, $pdf->GetPageHeight(), 0.01);
    }

    public function testDefaultMarginsAfterAddPage(): void
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $this->assertEqualsWithDelta(10.0, $pdf->GetX(), 0.01);
        $this->assertEqualsWithDelta(10.0, $pdf->GetY(), 0.01);
    }

    public function testPageRotationMetadata(): void
    {
        $pdf = new tFPDF();
        $pdf->AddPage('', '', 90);
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, 'rot');
        $bytes = $pdf->Output('S');
        $this->assertStringContainsString('/Rotate 90', $bytes);
    }

    public function testDisplayModeCatalogEntries(): void
    {
        $cases = [
            ['fullpage', 'default', '/Fit]'],
            ['fullwidth', 'default', '/FitH'],
            ['real', 'default', '/XYZ null null 1'],
            ['default', 'single', '/PageLayout /SinglePage'],
            ['default', 'continuous', '/PageLayout /OneColumn'],
            ['default', 'two', '/PageLayout /TwoColumnLeft'],
        ];
        foreach ($cases as [$zoom, $layout, $needle]) {
            $pdf = new tFPDF();
            $pdf->SetDisplayMode($zoom, $layout);
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', '', 12);
            $pdf->Cell(0, 10, 'x');
            $bytes = $pdf->Output('S');
            $this->assertStringContainsString($needle, $bytes, "$zoom/$layout");
        }
    }

    public function testNumericZoomInCatalog(): void
    {
        $pdf = new tFPDF();
        $pdf->SetDisplayMode(150);
        $pdf->AddPage();
        $bytes = $pdf->Output('S');
        $this->assertStringContainsString('/XYZ null null 1.50', $bytes);
    }

    public function testCloseAddsPageWhenNoneExists(): void
    {
        $pdf = new tFPDF();
        $bytes = $pdf->Output('S');
        PdfAssert::assertPdf($bytes);
        $this->assertSame(1, PdfAssert::pageCount($bytes));
    }

    public function testRootMediaBoxInPoints(): void
    {
        $pdf = new tFPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $bytes = $pdf->Output('S');
        $this->assertStringContainsString('/MediaBox [0 0 595.28 841.89]', $bytes);
    }

    public function testOperationsBeforeAddPageThrow(): void
    {
        $pdf = new tFPDF();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No page has been added yet');
        $pdf->Line(0, 0, 1, 1);
    }

    public function testSetFontBeforeAddPageIsAllowed(): void
    {
        $pdf = new tFPDF();
        $pdf->SetFont('Helvetica', '', 14);
        $pdf->AddPage();
        $bytes = $pdf->Output('S');
        PdfAssert::assertContainsInStreams($bytes, '14.00 Tf');
    }
}
