<?php
ob_start();

date_default_timezone_set("America/Mexico_City");

require_once "conexion.php";
require_once "session_check.php";
require_once __DIR__ . "/tcpdf/tcpdf.php";

require_login_json();

$loginSesion = trim($_SESSION["login"] ?? "");
$idPersonalSesion = (int)($_SESSION["idPersonal"] ?? 0);
$idpase = isset($_GET["idpase"]) ? trim((string)$_GET["idpase"]) : "";

if ($loginSesion === "" || $idPersonalSesion <= 0) {
    if (ob_get_length()) ob_end_clean();
    app_json_response(401, [
        "success" => false,
        "message" => "Sesión no válida."
    ]);
}

if ($idpase === "" || !ctype_digit($idpase)) {
    if (ob_get_length()) ob_end_clean();
    app_json_response(400, [
        "success" => false,
        "message" => "ID de pase no válido."
    ]);
}

function formatearFecha($fecha, $formato = "d/m/Y")
{
    if (empty($fecha) || $fecha === "0000-00-00 00:00:00") {
        return "";
    }

    try {
        $dt = new DateTime($fecha);
        return $dt->format($formato);
    } catch (Throwable $e) {
        return "";
    }
}

function textoPdf($valor)
{
    return trim((string)($valor ?? ""));
}

try {

    $sql = "SELECT 
                id,
                idpase,
                idpersonal,
                numcredencial,
                nombre,
                medico,
                especialidad,
                Fechaconsulta,
                FechaProbconsulta,
                fechapase,
                FechainicioVigencia,
                FechaFinvigencia,
                observaciones,
                direccion,
                secretaria,
                idconsulta
            FROM pasesmedicos_usuarios
            WHERE idpase = :idpase
              AND TRIM(numcredencial) = TRIM(:login)
            LIMIT 1";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([
        ":idpase" => $idpase,
        ":login" => $loginSesion
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        if (ob_get_length()) ob_end_clean();

        app_json_response(404, [
            "success" => false,
            "message" => "No se encontró el pase médico."
        ]);
    }

    $fechaHoy = date("Y-m-d H:i:s");
    $fechaTexto = date("d/m/Y");

    $sqlUpdate = "UPDATE pasesmedicos_usuarios 
                  SET 
                      fechadescarga = :fecha,
                      Fechaimpresion = :fecha,
                      origenimpresion = :origen
                  WHERE idpase = :idpase
                    AND TRIM(numcredencial) = TRIM(:login)";

    $stmtUpdate = $conexion->prepare($sqlUpdate);
    $stmtUpdate->execute([
        ":fecha" => $fechaHoy,
        ":origen" => "APP",
        ":idpase" => $idpase,
        ":login" => $loginSesion
    ]);

    $sqlQr = "SELECT qrcode
              FROM qr_paseaespecialista
              WHERE idpase = :idpase
                AND TRIM(numcredencial) = TRIM(:numcredencial)
                AND idpersonal = :idpersonal
              LIMIT 1";

    $stmtQr = $conexion->prepare($sqlQr);
    $stmtQr->execute([
        ":idpase" => $row["idpase"],
        ":numcredencial" => $row["numcredencial"],
        ":idpersonal" => $row["idpersonal"]
    ]);

    $qrRow = $stmtQr->fetch(PDO::FETCH_ASSOC);

    if ($qrRow && !empty($qrRow["qrcode"])) {
        $qrcode = $qrRow["qrcode"];
    } else {
        $qrcode = substr(bin2hex(random_bytes(16)), 0, 15);

        $sqlInsertQr = "INSERT INTO qr_paseaespecialista
                        (
                            numcredencial,
                            idpase,
                            especialidad,
                            nombre,
                            Fechaconsulta,
                            idconsulta,
                            qrcode,
                            fechacodigoalta,
                            idpersonal
                        )
                        VALUES
                        (
                            :numcredencial,
                            :idpase,
                            :especialidad,
                            :nombre,
                            :fechaconsulta,
                            :idconsulta,
                            :qrcode,
                            :fechacodigoalta,
                            :idpersonal
                        )";

        $stmtInsertQr = $conexion->prepare($sqlInsertQr);
        $stmtInsertQr->execute([
            ":numcredencial"   => $row["numcredencial"],
            ":idpase"          => $row["idpase"],
            ":especialidad"    => $row["especialidad"],
            ":nombre"          => $row["nombre"],
            ":fechaconsulta"   => $row["FechaProbconsulta"],
            ":idconsulta"      => $row["idconsulta"],
            ":qrcode"          => $qrcode,
            ":fechacodigoalta" => $fechaHoy,
            ":idpersonal"      => $row["idpersonal"]
        ]);
    }

    $ligaQr = "https://phpstack-1548792-5992674.cloudwaysapps.com/app/validar_pase/?idpase=" . urlencode($qrcode);

    $pdf = new TCPDF("P", "mm", "LETTER", true, "UTF-8", false);
    $pdf->SetCreator("App SUSPE");
    $pdf->SetAuthor("App SUSPE");
    $pdf->SetTitle("Pase Médico");
    $pdf->SetSubject("Pase Médico");
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(false, 10);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    $pdf->AddPage();

    $bg1 = $_SERVER["DOCUMENT_ROOT"] . "/mi_app/img/sys__NM__bg__NM__SM Pase Especialidades.png";
    if (is_file($bg1)) {
        $pdf->Image($bg1, 0, 0, 210, 150, "", "", "", false, 300, "", false, false, 0);
    }

    $fotoPath = $_SERVER["DOCUMENT_ROOT"] . "/app/_lib/file/img/fotos/" . (int)$row["idpersonal"] . ".jpg";

    if (!empty($row["idpersonal"]) && is_file($fotoPath)) {
        $pdf->StartTransform();
        $pdf->Ellipse(187.5, 77.5, 22.5, 17.5, 0, 0, 360, "CNZ");
        $pdf->Image($fotoPath, 165, 60, 45, 35, "", "", "", true, 300, "", false, false, 0, "CM", false, false);
        $pdf->StopTransform();
    } else {
        $pdf->Rect(165, 60, 45, 35, "D");
        $pdf->SetXY(165, 80);
        $pdf->SetFont("helvetica", "", 9);
        $pdf->Cell(45, 10, "Sin foto", 0, 0, "C");
    }

    $pdf->SetFont("helvetica", "", 9);

    $pdf->SetXY(20, 45);
    $pdf->Cell(0, 8, textoPdf($row["id"]), 0, 1, "L");

    $pdf->SetXY(90, 30);
    $pdf->Cell(0, 8, textoPdf($row["nombre"]), 0, 1, "L");

    $pdf->SetXY(47, 44);
    $pdf->Cell(0, 8, textoPdf($row["medico"]), 0, 1, "L");

    $pdf->SetXY(60, 110);
    $pdf->Cell(0, 8, textoPdf($row["especialidad"]), 0, 1, "L");

    $pdf->SetXY(20, 37);
    $pdf->Cell(0, 8, textoPdf($row["numcredencial"]), 0, 1, "L");

    $pdf->SetXY(10, 30);
    $pdf->Cell(0, 8, textoPdf($row["idpase"]), 0, 1, "L");

    $pdf->SetXY(10, 59);
    $pdf->Cell(0, 8, formatearFecha($row["FechainicioVigencia"]), 0, 1, "L");

    $pdf->SetXY(10, 71);
    $pdf->Cell(0, 8, $fechaTexto, 0, 1, "L");

    $pdf->SetXY(10, 81);
    $pdf->Cell(0, 8, formatearFecha($row["FechaFinvigencia"]), 0, 1, "L");

    $pdf->SetXY(60, 120);
    $pdf->MultiCell(170, 10, textoPdf($row["observaciones"]), 0, "L", false, 1);

    $pdf->SetXY(90, 142);
    $pdf->Cell(0, 10, "COPIA PARA ESPECIALISTA", 0, 1, "L");

    $styleQr = [
        "border" => 0,
        "padding" => 0,
        "fgcolor" => [0, 0, 0],
        "bgcolor" => false
    ];

    $pdf->write2DBarcode(
        $ligaQr,
        "QRCODE,H",
        11,
        105,
        32,
        32,
        $styleQr,
        "N"
    );

    $pdf->SetFont("helvetica", "", 7);
    $pdf->SetXY(158, 134);
    $pdf->Cell(32, 5, "Validar pase", 0, 1, "C");

    $pdf->AddPage();

    $bg2 = $_SERVER["DOCUMENT_ROOT"] . "/mi_app/img/usr__NM__bg__NM__SM__SOAP_Especialidades2.png";
    if (is_file($bg2)) {
        $pdf->Image($bg2, 0, 4, 210, 150, "", "", "", false, 300, "", false, false, 0);
    }

    $pdf->SetFont("helvetica", "", 8);

    $pdf->SetXY(60, 19);
    $pdf->Cell(0, 8, textoPdf($row["nombre"]), 0, 1, "L");

    $pdf->SetXY(140, 19);
    $pdf->Cell(0, 8, $fechaTexto, 0, 1, "L");

    $pdf->SetXY(28, 18);
    $pdf->Cell(0, 10, textoPdf($row["idpase"]), 0, 1, "L");

    $pdf->SetXY(45, 23);
    $pdf->Cell(0, 14, textoPdf($row["numcredencial"]), 0, 1, "L");

    $pdf->SetXY(123, 24);
    $pdf->Cell(0, 10, textoPdf($row["medico"]), 0, 1, "L");

    $pdf->write2DBarcode(
        $ligaQr,
        "QRCODE,H",
        185,
        17,
        20,
        20,
        $styleQr,
        "N"
    );

    $pdf->SetFont("helvetica", "", 7);
    $pdf->SetXY(158, 134);
    $pdf->Cell(32, 5, "Validar pase", 0, 1, "C");

    $nombreArchivo = "pase_medico_" .
        preg_replace("/[^0-9A-Za-z_-]/", "", $row["numcredencial"]) .
        "_" .
        preg_replace("/[^0-9A-Za-z_-]/", "", $row["idpase"]) .
        ".pdf";

    registrar_auditoria("DESCARGA_PASE_MEDICO", "idpase=" . $idpase);

    if (ob_get_length()) {
        ob_end_clean();
    }

    header("Content-Type: application/pdf");
    header("Content-Description: File Transfer");
    header("Content-Transfer-Encoding: binary");
    header("Content-Disposition: attachment; filename=\"" . $nombreArchivo . "\"; filename*=UTF-8''" . rawurlencode($nombreArchivo));
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");

    $pdf->Output($nombreArchivo, "D");
    exit;

} catch (Throwable $e) {
    if (ob_get_length()) {
        ob_end_clean();
    }

    app_json_response(500, [
        "success" => false,
        "message" => "Error al generar el pase médico."
    ]);
}