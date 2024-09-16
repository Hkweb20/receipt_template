<?php

namespace Quarkaxis\ReceiptTemplate\Service;

use Dompdf\Dompdf;

class PDFGenerator
{
    public function generatePDF(string $html, string $outputPath): void
    {
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        file_put_contents($outputPath, $dompdf->output());
    }
}
