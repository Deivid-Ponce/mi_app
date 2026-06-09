<?php
ob_start();

date_default_timezone_set('America/Mexico_City');

require_once __DIR__ . "/tcpdf/tcpdf.php";

try {
    $fecha = date("d/m/Y H:i:s");

    $pdf = new TCPDF();
    $pdf->SetCreator("mi_app");
    $pdf->SetAuthor("mi_app");
    $pdf->SetTitle("PDF simple");

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    $pdf->SetMargins(15, 20, 15);
    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 12, 'PDF CREADO', 0, 1, 'C');

    $pdf->Ln(10);

    $pdf->SetFont('helvetica', '', 14);
    $pdf->Cell(0, 10, 'Fecha: ' . $fecha, 0, 1, 'C');

    $nombreArchivo = "pdf_" . date("Ymd_His") . ".pdf";
    $carpetaPdf = $_SERVER['DOCUMENT_ROOT'] . "/mi_app/pdf/";
    $rutaPdf = $carpetaPdf . $nombreArchivo;

    if (!is_dir($carpetaPdf)) {
        mkdir($carpetaPdf, 0777, true);
    }

    if (ob_get_length()) {
        ob_end_clean();
    }

    $pdf->Output($rutaPdf, 'F');

    header("Location: /mi_app/pdf/" . rawurlencode($nombreArchivo));
    exit;

} catch (Throwable $e) {
    if (ob_get_length()) {
        ob_end_clean();
    }
    die("Error: " . $e->getMessage());
}