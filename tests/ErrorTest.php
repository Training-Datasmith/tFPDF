<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;

final class ErrorTest extends TfpdfTestCase
{
    /**
     * @return array<string, array{0: callable(): void, 1: string}>
     */
    public static function errorCases(): array
    {
        return [
            'bad unit' => [static function (): void {
                new tFPDF('P', 'px');
            }, 'Incorrect unit: px'],
            'bad orientation' => [static function (): void {
                new tFPDF('X');
            }, 'Incorrect orientation: x'],
            'unknown page size' => [static function (): void {
                $pdf = new tFPDF('P', 'mm', 'a2');
            }, 'Unknown page size: a2'],
            'bad rotation' => [static function (): void {
                $pdf = new tFPDF();
                $pdf->AddPage('', '', 45);
            }, 'Incorrect rotation value: 45'],
            'bad zoom' => [static function (): void {
                $pdf = new tFPDF();
                $pdf->SetDisplayMode('zoom');
            }, 'Incorrect zoom display mode: zoom'],
            'bad layout' => [static function (): void {
                $pdf = new tFPDF();
                $pdf->SetDisplayMode('default', 'grid');
            }, 'Incorrect layout display mode: grid'],
            'undefined font' => [static function (): void {
                $pdf = new tFPDF();
                $pdf->AddPage();
                $pdf->SetFont('DoesNotExist');
            }, 'Undefined font: doesnotexist'],
            'cell without font' => [static function (): void {
                $pdf = new tFPDF();
                $pdf->AddPage();
                $pdf->Cell(0, 10, 'x');
            }, 'No font has been set'],
            'bad font path' => [static function (): void {
                $pdf = new tFPDF();
                $pdf->AddFont('helvetica', '', 'a/b.php', false);
            }, 'Incorrect font definition file name: a/b.php'],
            'text before page' => [static function (): void {
                $pdf = new tFPDF();
                $pdf->SetFont('Helvetica', '', 12);
                $pdf->Text(1, 1, 'x');
            }, 'No page has been added yet'],
            'add page after close' => [static function (): void {
                $pdf = new tFPDF();
                $pdf->AddPage();
                $pdf->Output('S');
                $pdf->AddPage();
            }, 'The document is closed'],
            'empty image name' => [static function (): void {
                $pdf = new tFPDF();
                $pdf->AddPage();
                $pdf->Image('');
            }, 'Image file name is empty'],
            'image without extension' => [static function (): void {
                $pdf = new tFPDF();
                $pdf->AddPage();
                $pdf->Image('noext');
            }, 'Image file has no extension and no type was specified:'],
            'unsupported image type' => [static function (): void {
                $pdf = new tFPDF();
                $pdf->AddPage();
                $pdf->Image('a.bmp');
            }, 'Unsupported image type: bmp'],
            'bad output destination' => [static function (): void {
                $pdf = new tFPDF();
                $pdf->AddPage();
                $pdf->Output('Z');
            }, 'Incorrect output destination: Z'],
        ];
    }

    #[DataProvider('errorCases')]
    public function testTfpdfErrors(callable $action, string $message): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($message);
        $action();
    }
}
