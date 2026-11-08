<?php

class ErrorTest extends TfpdfTestCase
{
    public function errorCases()
    {
        return array(
            'bad unit' => array(function () {
                new tFPDF('P', 'px');
            }, 'Incorrect unit: px'),
            'bad orientation' => array(function () {
                new tFPDF('X');
            }, 'Incorrect orientation: x'),
            'unknown page size' => array(function () {
                new tFPDF('P', 'mm', 'a2');
            }, 'Unknown page size: a2'),
            'bad rotation' => array(function () {
                $pdf = new tFPDF();
                $pdf->AddPage('', '', 45);
            }, 'Incorrect rotation value: 45'),
            'bad zoom' => array(function () {
                $pdf = new tFPDF();
                $pdf->SetDisplayMode('zoom');
            }, 'Incorrect zoom display mode: zoom'),
            'bad layout' => array(function () {
                $pdf = new tFPDF();
                $pdf->SetDisplayMode('default', 'grid');
            }, 'Incorrect layout display mode: grid'),
            'undefined font' => array(function () {
                $pdf = new tFPDF();
                $pdf->AddPage();
                $pdf->SetFont('DoesNotExist');
            }, 'Undefined font: doesnotexist'),
            'cell without font' => array(function () {
                $pdf = new tFPDF();
                $pdf->AddPage();
                $pdf->Cell(0, 10, 'x');
            }, 'No font has been set'),
            'bad font path' => array(function () {
                $pdf = new tFPDF();
                $pdf->AddFont('helvetica', '', 'a/b.php', false);
            }, 'Incorrect font definition file name: a/b.php'),
            'text before page' => array(function () {
                $pdf = new tFPDF();
                $pdf->SetFont('Helvetica', '', 12);
                $pdf->Text(1, 1, 'x');
            }, 'No page has been added yet'),
            'add page after close' => array(function () {
                $pdf = new tFPDF();
                $pdf->AddPage();
                $pdf->Output('S');
                $pdf->AddPage();
            }, 'The document is closed'),
            'empty image name' => array(function () {
                $pdf = new tFPDF();
                $pdf->AddPage();
                $pdf->Image('');
            }, 'Image file name is empty'),
            'image without extension' => array(function () {
                $pdf = new tFPDF();
                $pdf->AddPage();
                $pdf->Image('noext');
            }, 'Image file has no extension and no type was specified:'),
            'unsupported image type' => array(function () {
                $pdf = new tFPDF();
                $pdf->AddPage();
                $pdf->Image('a.bmp');
            }, 'Unsupported image type: bmp'),
            'bad output destination' => array(function () {
                $pdf = new tFPDF();
                $pdf->AddPage();
                $pdf->Output('Z');
            }, 'Incorrect output destination: Z'),
        );
    }

    /**
     * @dataProvider errorCases
     */
    public function testTfpdfErrors($action, $message)
    {
        try {
            $action();
            $this->fail('Expected exception for: ' . $message);
        } catch (Exception $e) {
            $this->assertContains($message, $e->getMessage());
        }
    }
}
