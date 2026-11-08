<?php

class SeparateProcessTest extends TfpdfTestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testFontCacheFilesWrittenWhenUnifontWritable()
    {
        $root = $this->repoPath();
        $tmp = sys_get_temp_dir() . '/tfpdf-font-' . uniqid('', true);
        mkdir($tmp . '/unifont', 0777, true);
        $this->copyFontsForCacheTest($root, $tmp);

        if (!defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH', $tmp . '/');
        }

        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->AddFont('DejaVu', '', 'DejaVuSansCondensed.ttf', true);
        $pdf->SetFont('DejaVu', '', 12);
        $pdf->Write(5, 'cache');
        PdfAssert::assertPdf($pdf->Output('S'));

        $this->assertFileExists($tmp . '/unifont/dejavusanscondensed.mtx.php');
        $this->assertFileExists($tmp . '/unifont/dejavusanscondensed.cw.dat');
    }

    public function testTtfontDieOnGarbageFileInSubprocess()
    {
        $garbage = sys_get_temp_dir() . '/tfpdf-bad-font-' . uniqid('', true) . '.ttf';
        file_put_contents($garbage, 'not-a-font');
        $code = 'require ' . var_export($this->repoPath('tfpdf.php'), true) . ';'
            . 'require ' . var_export($this->repoPath('font/unifont/ttfonts.php'), true) . ';'
            . '$t = new TTFontFile(); $t->getMetrics(' . var_export($garbage, true) . ');';
        $cmd = escapeshellarg(PHP_BINARY) . ' -d display_errors=1 -r ' . escapeshellarg($code) . ' 2>&1';
        $output = array();
        $exit = 0;
        exec($cmd, $output, $exit);
        @unlink($garbage);
        $joined = implode("\n", $output);
        $this->assertContains('Not a TrueType font', $joined);
    }

    private function copyFontsForCacheTest($root, $tmp)
    {
        foreach (glob($root . '/font/*.php') ?: array() as $fontPhp) {
            copy($fontPhp, $tmp . '/' . basename($fontPhp));
        }
        foreach (glob($root . '/font/unifont/*.ttf') ?: array() as $ttf) {
            copy($ttf, $tmp . '/unifont/' . basename($ttf));
        }
    }
}
