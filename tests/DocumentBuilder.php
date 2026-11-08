<?php

declare(strict_types=1);

final class DocumentBuilder
{
    public static function coreOnePager(string $text = 'Test'): string
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, $text);

        return $pdf->Output('S');
    }
}
