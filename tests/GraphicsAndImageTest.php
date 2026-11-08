<?php

declare(strict_types=1);

final class GraphicsAndImageTest extends TfpdfTestCase
{
    private string $fixtureDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtureDir = sys_get_temp_dir() . '/tfpdf-test-images-' . getmypid();
        if (!is_dir($this->fixtureDir)) {
            mkdir($this->fixtureDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        foreach (glob($this->fixtureDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->fixtureDir);
        parent::tearDown();
    }

    public function testDrawColorAndLineOperators(): void
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

    public function testJpegImageEmbedding(): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD not available');
        }
        $path = $this->fixtureDir . '/1x1.jpg';
        $im = imagecreatetruecolor(1, 1);
        imagejpeg($im, $path);
        imagedestroy($im);

        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->Image($path, 10, 10, 0, 0);
        $bytes = $pdf->Output('S');
        $this->assertStringContainsString('/Subtype /Image', $bytes);
        $this->assertStringContainsString('/Filter /DCTDecode', $bytes);
        $this->assertStringContainsString('/ColorSpace /DeviceRGB', $bytes);
    }

    public function testPngRgbImageEmbedding(): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD not available');
        }
        $path = $this->fixtureDir . '/2x2.png';
        $im = imagecreatetruecolor(2, 2);
        imagepng($im, $path);
        imagedestroy($im);

        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->Image($path, 10, 10, 20, 20);
        $bytes = $pdf->Output('S');
        $this->assertStringContainsString('/ColorSpace /DeviceRGB', $bytes);
        $this->assertStringContainsString('/Subtype /Image', $bytes);
    }

    public function testPngWithAlphaUpgradesPdfVersion(): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD not available');
        }
        $path = $this->fixtureDir . '/alpha.png';
        $im = imagecreatetruecolor(2, 2);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefill($im, 0, 0, $transparent);
        imagepng($im, $path);
        imagedestroy($im);

        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->Image($path, 10, 10, 10, 10);
        $bytes = $pdf->Output('S');
        $this->assertStringStartsWith('%PDF-1.4', $bytes);
        $this->assertStringContainsString('/SMask', $bytes);
        $this->assertStringContainsString('/Transparency', $bytes);
    }

    public function testGifImageWhenSupported(): void
    {
        if (!function_exists('imagecreatefromgif') || !function_exists('imagegif')) {
            $this->markTestSkipped('GIF read support not available');
        }
        $path = $this->fixtureDir . '/1x1.gif';
        $im = imagecreatetruecolor(1, 1);
        imagegif($im, $path);
        imagedestroy($im);

        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->Image($path, 10, 10, 5, 5);
        $bytes = $pdf->Output('S');
        $this->assertStringContainsString('/Subtype /Image', $bytes);
    }
}
