<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . "/tcpdf/tcpdf.php";

$accion = isset($_GET["accion"]) ? trim($_GET["accion"]) : "";

if ($accion === "generar_pdf_simple") {
    generarPdfSimple();
    exit;
}

echo "Acción no válida";
exit;

function generarPdfSimple()
{
    if (ob_get_length()) {
        ob_end_clean();
    }

    date_default_timezone_set('America/Mexico_City');
    $fecha = date('d/m/Y H:i:s');

    $pdf = new TCPDF();
    $pdf->SetCreator("mi_app");
    $pdf->SetAuthor("mi_app");
    $pdf->SetTitle("PDF simple");
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 20, 15);
    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 10, 'PDF CREADO', 0, 1, 'C');

    $pdf->Ln(10);

    $pdf->SetFont('helvetica', '', 14);
    $pdf->Cell(0, 10, 'Fecha: ' . $fecha, 0, 1, 'C');

    $nombreArchivo = "pdf_creado.pdf";

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    $pdf->Output($nombreArchivo, 'D');
    exit;
}