<?php

class LayoutTest extends TfpdfTestCase
{
    public function testCellLineBreakModesMoveCoordinates()
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $w = 40.0;
        $h = 8.0;
        $pdf->Cell($w, $h, 'a', 0, 0);
        $this->assertEquals(10.0 + $w, $pdf->GetX(), '', 0.01);
        $y0 = $pdf->GetY();
        $pdf->Cell($w, $h, 'b', 0, 1);
        $this->assertEquals(10.0, $pdf->GetX(), '', 0.01);
        $this->assertEquals($y0 + $h, $pdf->GetY(), '', 0.01);
    }

    public function testMultiCellStripsCarriageReturns()
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->MultiCell(40, 6, "one\r\ntwo");
        $bytes = $pdf->Output('S');
        PdfAssert::assertContainsInStreams($bytes, '(one)');
        PdfAssert::assertContainsInStreams($bytes, '(two)');
    }

    public function testAutomaticPageBreakCreatesSecondPage()
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

    public function testHeaderAndFooterOnMultiplePages()
    {
        $pdf = new HeaderFooterPdf();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 8, 'body1', 0, 1);
        $pdf->AddPage();
        $pdf->Cell(0, 8, 'body2', 0, 1);
        $bytes = $pdf->Output('S');
        $streams = PdfAssert::inflateStreams($bytes);
        $this->assertSame(2, substr_count($streams, '(HEADER)'));
    }

    public function testAliasNbPagesReplacedInFooter()
    {
        $pdf = new FooterAliasPdf();
        $pdf->AliasNbPages('{nb}');
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 8, 'one', 0, 1);
        $pdf->AddPage();
        $pdf->Cell(0, 8, 'two', 0, 1);
        $bytes = $pdf->Output('S');
        PdfAssert::assertContainsInStreams($bytes, '(Pages: 2)');
        $this->assertNotContainsString('{nb}', PdfAssert::inflateStreams($bytes));
    }

    public function testExternalLinkAnnotation()
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Link(10, 10, 40, 10, 'https://example.com');
        $bytes = $pdf->Output('S');
        $this->assertContainsString('/URI (https://example.com)', $bytes);
    }

    public function testInternalLinkDestination()
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $link = $pdf->AddLink();
        $pdf->SetLink($link, 20, 1);
        $pdf->Link(10, 10, 30, 10, $link);
        $bytes = $pdf->Output('S');
        $this->assertContainsString('/Dest ', $bytes);
    }

    public function testNamespacedSubclass()
    {
        $doc = new Test\Pdf\NamespacedDocument();
        $doc->AddPage();
        $doc->SetFont('Helvetica', '', 12);
        $doc->Cell(0, 10, 'ns');
        $bytes = $doc->Output('S');
        PdfAssert::assertPdf($bytes);
    }
}

class HeaderFooterPdf extends tFPDF
{
    public function Header()
    {
        $this->SetFont('Helvetica', '', 10);
        $this->Cell(0, 8, 'HEADER', 0, 1);
    }
}

class FooterAliasPdf extends tFPDF
{
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Helvetica', '', 10);
        $this->Cell(0, 10, 'Pages: {nb}', 0, 0, 'C');
    }
}
