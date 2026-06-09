<?php
ob_start();

date_default_timezone_set("America/Mexico_City");

require_once "conexion.php";
require_once "session_check.php";
require_once __DIR__ . "/tcpdf/tcpdf.php";

require_login_json();

$loginSesion = trim($_SESSION["login"] ?? "");
$idestudio = isset($_GET["idestudio"]) ? (int)$_GET["idestudio"] : 0;
$idpersonal = isset($_GET["idpersonal"]) ? (int)$_GET["idpersonal"] : 0;

if ($loginSesion === "") {
    if (ob_get_length()) ob_end_clean();
    app_json_response(401, [
        "success" => false,
        "message" => "Sesión no válida."
    ]);
}

if ($idestudio <= 0 || $idpersonal <= 0) {
    if (ob_get_length()) ob_end_clean();
    app_json_response(400, [
        "success" => false,
        "message" => "Parámetros no válidos."
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

function marcarDescargaEstudioSeguro($conexion, $idestudio, $idpersonal, $loginSesion)
{
    $urlApiRemota = "https://suspe.ddns.net/Cloud_API/api_marcar_descarga_estudio_sqlserver.php";

    try {
        $sqlLocal = "UPDATE WebEstudiosClinicosAppImprimir
                     SET Fechaimpresion = NOW(),
                         origenimpresion = :origenimpresion,
                         fechadescarga = NOW()
                     WHERE idestudio = :idestudio
                       AND idpersonal = :idpersonal
                       AND numcredencial = :login";

        $stmtLocal = $conexion->prepare($sqlLocal);
        $stmtLocal->execute([
            ":origenimpresion" => "APP",
            ":idestudio" => $idestudio,
            ":idpersonal" => $idpersonal,
            ":login" => $loginSesion
        ]);

        if ($stmtLocal->rowCount() <= 0) {
            return false;
        }

        $payload = json_encode([
            "idestudio" => (int)$idestudio,
            "idpersonal" => (int)$idpersonal
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init($urlApiRemota);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Content-Length: " . strlen($payload)
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);

        $respuestaRemota = curl_exec($ch);

        if ($respuestaRemota === false) {
            curl_close($ch);
            return false;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $jsonRemoto = json_decode($respuestaRemota, true);

        if ($httpCode !== 200 || !is_array($jsonRemoto) || empty($jsonRemoto["success"])) {
            return false;
        }

        return true;

    } catch (Throwable $e) {
        return false;
    }
}

try {
    /*
      Validación principal:
      El estudio debe pertenecer al usuario autenticado.
    */
    $sql = "SELECT
                idpersonal,
                idestudio,
                numcredencial,
                numempleado,
                nombre,
                relacion,
                medico,
                idtipoestudio,
                estudio,
                idA,
                FechainicioVigencia,
                Fechaconsulta,
                FechaFinvigencia,
                FechaProbconsulta,
                secretaria,
                Laboratorio,
                Direcccion,
                cadenaFecha,
                Idespecialidad,
                especialidad,
                Fechaimpresion,
                Estatus,
                FechaToma,
                conciliado,
                origenimpresion,
                fechaestudio,
                idconsulta,
                idlaboratorio,
                urgente,
                CVEIMPRESION,
                fechadescarga
            FROM WebEstudiosClinicosAppImprimir
            WHERE idestudio = :idestudio
              AND idpersonal = :idpersonal
              AND numcredencial = :login
            LIMIT 1";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([
        ":idestudio" => $idestudio,
        ":idpersonal" => $idpersonal,
        ":login" => $loginSesion
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        if (ob_get_length()) ob_end_clean();
        app_json_response(404, [
            "success" => false,
            "message" => "No se encontró el pase clínico."
        ]);
    }

    $resultadoMarca = marcarDescargaEstudioSeguro(
        $conexion,
        $idestudio,
        $idpersonal,
        $loginSesion
    );

    if (!$resultadoMarca) {
        if (ob_get_length()) ob_end_clean();
        app_json_response(502, [
            "success" => false,
            "message" => "No fue posible marcar la descarga del estudio."
        ]);
    }

    $fechaTexto = date("d/m/Y");

    /*
      QR simple.
      Recomendación futura: guardar este código en tabla y validarlo contra BD.
    */
    $codigoQr = hash("sha256", $row["idestudio"] . "|" . $row["idpersonal"] . "|" . $row["numcredencial"]);
    $codigoQr = substr($codigoQr, 0, 32);

    $ligaQr = "https://phpstack-1548792-5992674.cloudwaysapps.com/app/validar_estudio_clinico/?code=" . urlencode($codigoQr);

    $pdf = new TCPDF("P", "mm", "LETTER", true, "UTF-8", false);
    $pdf->SetCreator("App SUSPE");
    $pdf->SetAuthor("App SUSPE");
    $pdf->SetTitle("Pase Clínico");
    $pdf->SetSubject("Pase Clínico");
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(false, 10);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    $pdf->AddPage();

    $bg1 = $_SERVER["DOCUMENT_ROOT"] . "/mi_app/img/usr__NM__bg__NM__ExamenClinico.jpg";
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

    $pdf->SetXY(20, 30);
    $pdf->Cell(0, 8, textoPdf($row["idestudio"]), 0, 1, "L");

    $pdf->SetXY(20, 37);
    $pdf->Cell(0, 8, textoPdf($row["numcredencial"]), 0, 1, "L");

    $pdf->SetXY(20, 44);
    $pdf->Cell(0, 8, textoPdf($row["idpersonal"]), 0, 1, "L");

    $pdf->SetXY(92, 37);
    $pdf->Cell(0, 8, textoPdf($row["secretaria"]), 0, 1, "L");

    $pdf->SetXY(57, 30);
    $pdf->Cell(0, 8, textoPdf($row["numempleado"]), 0, 1, "L");

    $pdf->SetXY(90, 30);
    $pdf->Cell(0, 8, textoPdf($row["nombre"]), 0, 1, "L");

    $pdf->SetXY(47, 44);
    $pdf->Cell(0, 8, textoPdf($row["medico"]), 0, 1, "L");

    $pdf->SetXY(60, 95);
    $pdf->Cell(0, 8, textoPdf($row["estudio"]), 0, 1, "L");

    $pdf->SetXY(10, 59);
    $pdf->Cell(0, 8, formatearFecha($row["Fechaconsulta"]), 0, 1, "L");

    $pdf->SetXY(10, 71);
    $pdf->Cell(0, 8, $fechaTexto, 0, 1, "L");

    $pdf->SetXY(10, 82);
    $pdf->Cell(0, 8, formatearFecha($row["FechaFinvigencia"]), 0, 1, "L");

    $pdf->SetXY(80, 126);
    $pdf->Cell(0, 8, textoPdf($row["Laboratorio"]), 0, 1, "L");

    $pdf->SetXY(76, 136);
    $pdf->MultiCell(120, 8, textoPdf($row["Direcccion"]), 0, "L", false, 1);

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

    /*
      Salida directa, no guarda archivo público.
    */
    $nombreArchivo = "pase_clinico_" .
        preg_replace("/[^0-9A-Za-z_-]/", "", $row["numcredencial"]) .
        "_" .
        preg_replace("/[^0-9A-Za-z_-]/", "", $row["idestudio"]) .
        ".pdf";
        
        registrar_auditoria(
            "DESCARGA_PASE_CLINICO",
            "idestudio=" . $idestudio . "; idpersonal=" . $idpersonal
        );
        

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
        "message" => "Error al generar el pase clínico."
    ]);
}