<?php

class DocumentBuilder
{
    public static function coreOnePager($text = 'Test')
    {
        $pdf = new tFPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, $text);

        return $pdf->Output('S');
    }
}
