<?php

class ConstructorAndPagesTest extends TfpdfTestCase
{
    public function testA4PortraitDimensionsInMillimetres()
    {
        $pdf = new tFPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $this->assertEquals(210.0, $pdf->GetPageWidth(), '', 0.02);
        $this->assertEquals(297.0, $pdf->GetPageHeight(), '', 0.02);
    }

    public function testA4LandscapeSwapsDimensions()
    {
        $pdf = new tFPDF('L', 'mm', 'A4');
        $pdf->AddPage();
        $this->assertEquals(297.0, $pdf->GetPageWidth(), '', 0.02);
    }

    public function testCustomSizeNormalizesShorterSideFirstForPortrait()
    {
        $pdf = new tFPDF('P', 'mm', array(200, 100));
        $pdf->AddPage();
        $this->assertEquals(100.0, $pdf->GetPageWidth(), '', 0.01);
        $this->assertEquals(200.0, $pdf->GetPageHeight(), '', 0.01);
    }

    public function testCustomSizeLandscape()
    {
        $pdf = new tFPDF('L', 'mm', array(200, 100));
        $pdf->AddPage();
        $this->assertEquals(200.0, $pdf->GetPageWidth(), '', 0.01);
        $this->assertEquals(100.0, $pdf->GetPageHeight(), '', 0.01);
    }

    public function testDefaultMarginsAfterAddPage()
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $this->assertEquals(10.0, $pdf->GetX(), '', 0.01);
        $this->assertEquals(10.0, $pdf->GetY(), '', 0.01);
    }

    public function testPageRotationMetadata()
    {
        $pdf = new tFPDF();
        $pdf->AddPage('', '', 90);
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, 'rot');
        $bytes = $pdf->Output('S');
        $this->assertContainsString('/Rotate 90', $bytes);
    }

    public function testDisplayModeCatalogEntries()
    {
        $cases = array(
            array('fullpage', 'default', '/Fit]'),
            array('fullwidth', 'default', '/FitH'),
            array('real', 'default', '/XYZ null null 1'),
            array('default', 'single', '/PageLayout /SinglePage'),
            array('default', 'continuous', '/PageLayout /OneColumn'),
            array('default', 'two', '/PageLayout /TwoColumnLeft'),
        );
        foreach ($cases as $case) {
            $pdf = new tFPDF();
            $pdf->SetDisplayMode($case[0], $case[1]);
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', '', 12);
            $pdf->Cell(0, 10, 'x');
            $bytes = $pdf->Output('S');
            $this->assertContainsString($case[2], $bytes, $case[0] . '/' . $case[1]);
        }
    }

    public function testNumericZoomInCatalog()
    {
        $pdf = new tFPDF();
        $pdf->SetDisplayMode(150);
        $pdf->AddPage();
        $bytes = $pdf->Output('S');
        $this->assertContainsString('/XYZ null null 1.50', $bytes);
    }

    public function testCloseAddsPageWhenNoneExists()
    {
        $pdf = new tFPDF();
        $bytes = $pdf->Output('S');
        PdfAssert::assertPdf($bytes);
        $this->assertSame(1, PdfAssert::pageCount($bytes));
    }

    public function testRootMediaBoxInPoints()
    {
        $pdf = new tFPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $bytes = $pdf->Output('S');
        $this->assertContainsString('/MediaBox [0 0 595.28 841.89]', $bytes);
    }

    public function testOperationsBeforeAddPageThrow()
    {
        $pdf = new tFPDF();
        try {
            $pdf->Line(0, 0, 1, 1);
            $this->fail('Expected exception');
        } catch (Exception $e) {
            $this->assertContains('No page has been added yet', $e->getMessage());
        }
    }

    public function testSetFontBeforeAddPageIsAllowed()
    {
        $pdf = new tFPDF();
        $pdf->SetFont('Helvetica', '', 14);
        $pdf->AddPage();
        $bytes = $pdf->Output('S');
        PdfAssert::assertContainsInStreams($bytes, '14.00 Tf');
    }
}
