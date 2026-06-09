<?php
require_once "conexion.php";
require_once "session_check.php";

require_login_json();

$login = trim($_SESSION["login"] ?? "");

if ($login === "") {
    app_json_response(401, [
        "success" => false,
        "message" => "Sesión no válida."
    ]);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    app_json_response(405, [
        "success" => false,
        "message" => "Método no permitido."
    ]);
}

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    app_json_response(400, [
        "success" => false,
        "message" => "JSON inválido."
    ]);
}

$idestudio = isset($data["idestudio"]) ? (int)$data["idestudio"] : 0;
$idpersonal = isset($data["idpersonal"]) ? (int)$data["idpersonal"] : 0;
$laboratorio = isset($data["laboratorio"]) ? trim((string)$data["laboratorio"]) : "";

if ($idestudio <= 0 || $idpersonal <= 0 || $laboratorio === "") {
    app_json_response(400, [
        "success" => false,
        "message" => "Datos incompletos para guardar el laboratorio."
    ]);
}

$mapaLaboratorios = [
    "MOREIRA" => 10,
    "BIOMED"  => 564
];

$laboratorioNormalizado = strtoupper($laboratorio);

if (!isset($mapaLaboratorios[$laboratorioNormalizado])) {
    app_json_response(400, [
        "success" => false,
        "message" => "Laboratorio no válido."
    ]);
}

$idlaboratorio = $mapaLaboratorios[$laboratorioNormalizado];

$urlApiRemota = "https://suspe.ddns.net/Cloud_API/api_actualizar_laboratorio_estudio_sqlserver.php";

try {
    /*
      Validar que el estudio/persona pertenece a la credencial de la sesión.
      Esto evita que alguien mande otro idpersonal/idestudio y modifique datos ajenos.
    */
    $stmtValidar = $conexion->prepare("
        SELECT idestudio
        FROM WebEstudiosClinicosAppImprimir
        WHERE idestudio = :idestudio
          AND idpersonal = :idpersonal
          AND numcredencial = :login
        LIMIT 1
    ");

    $stmtValidar->execute([
        ":idestudio" => $idestudio,
        ":idpersonal" => $idpersonal,
        ":login" => $login
    ]);

    if (!$stmtValidar->fetch(PDO::FETCH_ASSOC)) {
        app_json_response(403, [
            "success" => false,
            "message" => "No tienes permiso para actualizar este estudio."
        ]);
    }

    $conexion->beginTransaction();

    $sql = "UPDATE WebEstudiosClinicosAppImprimir
            SET idlaboratorio = :idlaboratorio,
                Laboratorio = :laboratorio
            WHERE idestudio = :idestudio
              AND idpersonal = :idpersonal
              AND numcredencial = :login";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([
        ":idlaboratorio" => $idlaboratorio,
        ":laboratorio" => $laboratorioNormalizado,
        ":idestudio" => $idestudio,
        ":idpersonal" => $idpersonal,
        ":login" => $login
    ]);

    if ($stmt->rowCount() <= 0) {
        $conexion->rollBack();

        app_json_response(404, [
            "success" => false,
            "message" => "No se encontró el estudio local para actualizar."
        ]);
    }

    $payload = json_encode([
        "idestudio" => $idestudio,
        "idpersonal" => $idpersonal,
        "laboratorio" => $laboratorioNormalizado
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
        $conexion->rollBack();

        app_json_response(502, [
            "success" => false,
            "message" => "No fue posible sincronizar con SQL Server."
        ]);
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $jsonRemoto = json_decode($respuestaRemota, true);

    if ($httpCode !== 200 || !is_array($jsonRemoto) || empty($jsonRemoto["success"])) {
        $conexion->rollBack();

        app_json_response(502, [
            "success" => false,
            "message" => "SQL Server no confirmó la actualización."
        ]);
    }

    $conexion->commit();

    app_json_response(200, [
        "success" => true,
        "message" => "Laboratorio asignado correctamente.",
        "idlaboratorio" => $idlaboratorio,
        "laboratorio" => $laboratorioNormalizado
    ]);

} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    app_json_response(500, [
        "success" => false,
        "message" => "Error al guardar el laboratorio."
    ]);
}