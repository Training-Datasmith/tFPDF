<?php

class UnicodeFontTest extends TfpdfTestCase
{
    public function testDejaVuType0Structure()
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->AddFont('DejaVu', '', 'DejaVuSansCondensed.ttf', true);
        $pdf->SetFont('DejaVu', '', 14);
        $pdf->Write(8, 'Unicode');
        $bytes = $pdf->Output('S');
        PdfAssert::assertPdf($bytes);
        $this->assertContainsString('/Subtype /Type0', $bytes);
        $this->assertContainsString('/Encoding /Identity-H', $bytes);
        $this->assertContainsString('/Subtype /CIDFontType2', $bytes);
        $this->assertContainsString('/FontFile2', $bytes);
        $this->assertContainsString('MPDFAA+', $bytes);
        $this->assertContainsString('/CMapName /Adobe-Identity-UCS', $bytes);
    }

    public function testHelloWorldSampleMatchesExPhp()
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->AddFont('DejaVu', '', 'DejaVuSansCondensed.ttf', true);
        $pdf->SetFont('DejaVu', '', 14);
        $txt = file_get_contents($this->repoPath('HelloWorld.txt'));
        $this->assertTrue($txt !== false);
        $pdf->Write(8, $txt);
        $pdf->SetFont('Arial', '', 14);
        $pdf->Ln(10);
        $pdf->Write(5, 'The file size of this PDF is only 13 KB.');
        $bytes = $pdf->Output('S');

        $searchHaystack = PdfAssert::inflateStreams($bytes) . $bytes;
        $lines = array(
            'Hello World',
            'Γειά σου κόσμος',
            'Witaj świecie',
            'Olá mundo',
            'Здравствулте мир',
            'Xin chào thế giới',
        );
        foreach ($lines as $line) {
            $utf16 = mb_convert_encoding($line, 'UTF-16BE', 'UTF-8');
            $this->assertContainsString($utf16, $searchHaystack, $line);
        }
        PdfAssert::assertContainsInStreams($bytes, '(The file size of this PDF is only 13 KB.)');
    }

    public function testDejaVuStringWidth()
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->AddFont('DejaVu', '', 'DejaVuSansCondensed.ttf', true);
        $pdf->SetFont('DejaVu', '', 14);
        $this->assertGreaterThan(0.0, $pdf->GetStringWidth('Test'));
        $this->assertGreaterThan(0.0, $pdf->GetStringWidth('κόσμος'));
        $this->assertSame(0.0, $pdf->GetStringWidth(''));
    }

    public function testFontStreamsStayFlateWhenPageCompressionDisabled()
    {
        $pdf = new tFPDF();
        $pdf->SetCompression(false);
        $pdf->AddPage();
        $pdf->AddFont('DejaVu', '', 'DejaVuSansCondensed.ttf', true);
        $pdf->SetFont('DejaVu', '', 14);
        $pdf->Write(8, 'x');
        $bytes = $pdf->Output('S');
        $this->assertNotRegExp(
            '/4 0 obj\s*<<[^>]*\/Filter \/FlateDecode/s',
            $bytes,
            'page content stream should stay uncompressed'
        );
        $this->assertContainsString('/Filter /FlateDecode', $bytes, 'font payloads remain compressed');
    }
}
