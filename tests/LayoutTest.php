<?php

declare(strict_types=1);

final class LayoutTest extends TfpdfTestCase
{
    public function testCellLineBreakModesMoveCoordinates(): void
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $w = 40.0;
        $h = 8.0;
        $pdf->Cell($w, $h, 'a', 0, 0);
        $this->assertEqualsWithDelta(10.0 + $w, $pdf->GetX(), 0.01);
        $y0 = $pdf->GetY();
        $pdf->Cell($w, $h, 'b', 0, 1);
        $this->assertEqualsWithDelta(10.0, $pdf->GetX(), 0.01);
        $this->assertEqualsWithDelta($y0 + $h, $pdf->GetY(), 0.01);
    }

    public function testMultiCellStripsCarriageReturns(): void
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->MultiCell(40, 6, "one\r\ntwo");
        $bytes = $pdf->Output('S');
        PdfAssert::assertContainsInStreams($bytes, '(one)');
        PdfAssert::assertContainsInStreams($bytes, '(two)');
    }

    public function testAutomaticPageBreakCreatesSecondPage(): void
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        for ($i = 0; $i < 40; ++$i) {
            $pdf->Cell(0, 8, 'Line ' . $i, 0, 1);
        }
        $bytes = $pdf->Output('S');
        $this->assertSame(2, PdfAssert::pageCount($bytes));
    }

    public function testHeaderAndFooterOnMultiplePages(): void
    {
        $pdf = new class extends tFPDF {
            public function Header(): void
            {
                $this->SetFont('Helvetica', '', 10);
                $this->Cell(0, 8, 'HEADER', 0, 1);
            }
        };
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 8, 'body1', 0, 1);
        $pdf->AddPage();
        $pdf->Cell(0, 8, 'body2', 0, 1);
        $bytes = $pdf->Output('S');
        $streams = PdfAssert::inflateStreams($bytes);
        $this->assertSame(2, substr_count($streams, '(HEADER)'));
    }

    public function testAliasNbPagesReplacedInFooter(): void
    {
        $pdf = new class extends tFPDF {
            public function Footer(): void
            {
                $this->SetY(-15);
                $this->SetFont('Helvetica', '', 10);
                $this->Cell(0, 10, 'Pages: {nb}', 0, 0, 'C');
            }
        };
        $pdf->AliasNbPages('{nb}');
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 8, 'one', 0, 1);
        $pdf->AddPage();
        $pdf->Cell(0, 8, 'two', 0, 1);
        $bytes = $pdf->Output('S');
        PdfAssert::assertContainsInStreams($bytes, '(Pages: 2)');
        $this->assertStringNotContainsString('{nb}', PdfAssert::inflateStreams($bytes));
    }

    public function testExternalLinkAnnotation(): void
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Link(10, 10, 40, 10, 'https://example.com');
        $bytes = $pdf->Output('S');
        $this->assertStringContainsString('/URI (https://example.com)', $bytes);
    }

    public function testInternalLinkDestination(): void
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $link = $pdf->AddLink();
        $pdf->SetLink($link, 20, 1);
        $pdf->Link(10, 10, 30, 10, $link);
        $bytes = $pdf->Output('S');
        $this->assertStringContainsString('/Dest ', $bytes);
    }

    public function testNamespacedSubclass(): void
    {
        $doc = new Test\Pdf\NamespacedDocument();
        $doc->AddPage();
        $doc->SetFont('Helvetica', '', 12);
        $doc->Cell(0, 10, 'ns');
        $bytes = $doc->Output('S');
        PdfAssert::assertPdf($bytes);
    }
}
