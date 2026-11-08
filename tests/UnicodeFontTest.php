<?php

declare(strict_types=1);

final class UnicodeFontTest extends TfpdfTestCase
{
    public function testDejaVuType0Structure(): void
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->AddFont('DejaVu', '', 'DejaVuSansCondensed.ttf', true);
        $pdf->SetFont('DejaVu', '', 14);
        $pdf->Write(8, 'Unicode');
        $bytes = $pdf->Output('S');
        PdfAssert::assertPdf($bytes);
        $this->assertStringContainsString('/Subtype /Type0', $bytes);
        $this->assertStringContainsString('/Encoding /Identity-H', $bytes);
        $this->assertStringContainsString('/Subtype /CIDFontType2', $bytes);
        $this->assertStringContainsString('/FontFile2', $bytes);
        $this->assertStringContainsString('MPDFAA+', $bytes);
        $this->assertStringContainsString('/CMapName /Adobe-Identity-UCS', $bytes);
    }

    public function testHelloWorldSampleMatchesExPhp(): void
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->AddFont('DejaVu', '', 'DejaVuSansCondensed.ttf', true);
        $pdf->SetFont('DejaVu', '', 14);
        $txt = file_get_contents($this->repoPath('HelloWorld.txt'));
        $this->assertNotFalse($txt);
        $pdf->Write(8, $txt);
        $pdf->SetFont('Arial', '', 14);
        $pdf->Ln(10);
        $pdf->Write(5, 'The file size of this PDF is only 13 KB.');
        $bytes = $pdf->Output('S');

        $searchHaystack = PdfAssert::inflateStreams($bytes) . $bytes;
        foreach ([
            'Hello World',
            'Γειά σου κόσμος',
            'Witaj świecie',
            'Olá mundo',
            'Здравствулте мир',
            'Xin chào thế giới',
        ] as $line) {
            $utf16 = mb_convert_encoding($line, 'UTF-16BE', 'UTF-8');
            $this->assertStringContainsString($utf16, $searchHaystack, $line);
        }
        PdfAssert::assertContainsInStreams($bytes, '(The file size of this PDF is only 13 KB.)');
    }

    public function testDejaVuStringWidth(): void
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->AddFont('DejaVu', '', 'DejaVuSansCondensed.ttf', true);
        $pdf->SetFont('DejaVu', '', 14);
        $this->assertGreaterThan(0.0, $pdf->GetStringWidth('Test'));
        $this->assertGreaterThan(0.0, $pdf->GetStringWidth('κόσμος'));
        $this->assertSame(0.0, $pdf->GetStringWidth(''));
    }

    public function testFontStreamsStayFlateWhenPageCompressionDisabled(): void
    {
        $pdf = new tFPDF();
        $pdf->SetCompression(false);
        $pdf->AddPage();
        $pdf->AddFont('DejaVu', '', 'DejaVuSansCondensed.ttf', true);
        $pdf->SetFont('DejaVu', '', 14);
        $pdf->Write(8, 'x');
        $bytes = $pdf->Output('S');
        $this->assertDoesNotMatchRegularExpression(
            '/4 0 obj\s*<<[^>]*\/Filter \/FlateDecode/s',
            $bytes,
            'page content stream should stay uncompressed'
        );
        $this->assertStringContainsString('/Filter /FlateDecode', $bytes, 'font payloads remain compressed');
    }
}
