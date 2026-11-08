<?php

class GraphicsAndImageTest extends TfpdfTestCase
{
    public function testDrawColorAndLineOperators()
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->SetDrawColor(255, 0, 0);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(10, 10, 20, 20);
        $pdf->Rect(30, 30, 20, 15, 'FD');
        $bytes = $pdf->Output('S');
        PdfAssert::assertContainsInStreams($bytes, '1.000 0.000 0.000 RG');
        PdfAssert::assertContainsInStreams($bytes, '1.42 w');
        PdfAssert::assertContainsInStreams($bytes, ' l S');
    }

    public function testJpegImageEmbedding()
    {
        $path = $this->repoPath('tests/fixtures/1x1.jpg');
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->Image($path, 10, 10, 0, 0);
        $bytes = $pdf->Output('S');
        $this->assertContainsString('/Subtype /Image', $bytes);
        $this->assertContainsString('/Filter /DCTDecode', $bytes);
        $this->assertContainsString('/ColorSpace /DeviceRGB', $bytes);
    }

    public function testPngRgbImageEmbedding()
    {
        $path = $this->repoPath('tests/fixtures/2x2.png');
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->Image($path, 10, 10, 20, 20);
        $bytes = $pdf->Output('S');
        $this->assertContainsString('/ColorSpace /DeviceRGB', $bytes);
        $this->assertContainsString('/Subtype /Image', $bytes);
    }

    public function testPngWithAlphaUpgradesPdfVersion()
    {
        $path = $this->repoPath('tests/fixtures/alpha.png');
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->Image($path, 10, 10, 10, 10);
        $bytes = $pdf->Output('S');
        $this->assertStringStartsWith('%PDF-1.4', $bytes);
        $this->assertContainsString('/SMask', $bytes);
        $this->assertContainsString('/Transparency', $bytes);
    }

}
