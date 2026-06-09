<?php
require_once "conexion.php";
require_once "session_check.php";

require_login_json();

$login = trim($_SESSION["login"] ?? "");
$idpersonal = isset($_GET["idpersonal"]) ? (int)$_GET["idpersonal"] : 0;

if ($login === "") {
    app_json_response(401, [
        "success" => false,
        "message" => "Sesión no válida."
    ]);
}

if ($idpersonal <= 0) {
    app_json_response(400, [
        "success" => false,
        "message" => "El parámetro idpersonal no es válido."
    ]);
}

/* Validar que la persona pertenece a la credencial en sesión */
try {
    $stmtPersona = $conexion->prepare("
        SELECT IdPersonal
        FROM persona
        WHERE IdPersonal = :idpersonal
          AND NumCredencial = :login
          AND IdCausaBaja NOT IN (2, 4, 5)
          AND (
                FechaVigencia >= CURDATE()
                OR FechaVigencia IS NULL
              )
        LIMIT 1
    ");

    $stmtPersona->execute([
        ":idpersonal" => $idpersonal,
        ":login" => $login
    ]);

    if (!$stmtPersona->fetch(PDO::FETCH_ASSOC)) {
        app_json_response(403, [
            "success" => false,
            "message" => "La persona no pertenece a esta credencial."
        ]);
    }

    $config_api = require "/home/1548792.cloudwaysapps.com/bpnhfkwfyb/private_html/cloud_config_estudiosclinicos.php";

    $base_url = $config_api["API_URL"];
    $token = $config_api["API_TOKEN"];

    $url = $base_url . "?idpersonal=" . urlencode((string)$idpersonal) . "&token=" . urlencode($token);

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        curl_close($ch);
        throw new Exception("Error al consumir API");
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        throw new Exception("API HTTP inválida");
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("API JSON inválido");
    }

    if (!isset($data["success"]) || $data["success"] !== true) {
        throw new Exception("API devolvió error");
    }

    if (!isset($data["data"]) || !is_array($data["data"])) {
        throw new Exception("API sin registros válidos");
    }

    $conexion->beginTransaction();

    $sqlDelete = "DELETE FROM WebEstudiosClinicosAppImprimir
                  WHERE idpersonal = :idpersonal";

    $stmtDelete = $conexion->prepare($sqlDelete);
    $stmtDelete->execute([
        ":idpersonal" => $idpersonal
    ]);

    $sqlInsert = "INSERT INTO WebEstudiosClinicosAppImprimir (
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
    ) VALUES (
        :idpersonal,
        :idestudio,
        :numcredencial,
        :numempleado,
        :nombre,
        :relacion,
        :medico,
        :idtipoestudio,
        :estudio,
        :idA,
        :FechainicioVigencia,
        :Fechaconsulta,
        :FechaFinvigencia,
        :FechaProbconsulta,
        :secretaria,
        :Laboratorio,
        :Direcccion,
        :cadenaFecha,
        :Idespecialidad,
        :especialidad,
        :Fechaimpresion,
        :Estatus,
        :FechaToma,
        :conciliado,
        :origenimpresion,
        :fechaestudio,
        :idconsulta,
        :idlaboratorio,
        :urgente,
        :CVEIMPRESION,
        :fechadescarga
    )";

    $stmtInsert = $conexion->prepare($sqlInsert);

    foreach ($data["data"] as $row) {
        $stmtInsert->execute([
            ":idpersonal"          => $idpersonal,
            ":idestudio"           => !empty($row["idestudio"]) ? (int)$row["idestudio"] : null,
            ":numcredencial"       => $row["numcredencial"] ?? $login,
            ":numempleado"         => !empty($row["numempleado"]) ? (int)$row["numempleado"] : null,
            ":nombre"              => $row["nombre"] ?? null,
            ":relacion"            => $row["relacion"] ?? null,
            ":medico"              => $row["medico"] ?? null,
            ":idtipoestudio"       => !empty($row["idtipoestudio"]) ? (int)$row["idtipoestudio"] : null,
            ":estudio"             => $row["estudio"] ?? null,
            ":idA"                 => !empty($row["idA"]) ? (int)$row["idA"] : null,
            ":FechainicioVigencia" => !empty($row["FechainicioVigencia"]) ? $row["FechainicioVigencia"] : null,
            ":Fechaconsulta"       => !empty($row["Fechaconsulta"]) ? $row["Fechaconsulta"] : null,
            ":FechaFinvigencia"    => !empty($row["FechaFinvigencia"]) ? $row["FechaFinvigencia"] : null,
            ":FechaProbconsulta"   => !empty($row["FechaProbconsulta"]) ? $row["FechaProbconsulta"] : null,
            ":secretaria"          => $row["secretaria"] ?? null,
            ":Laboratorio"         => $row["Laboratorio"] ?? null,
            ":Direcccion"          => $row["Direcccion"] ?? null,
            ":cadenaFecha"         => $row["cadenaFecha"] ?? null,
            ":Idespecialidad"      => !empty($row["Idespecialidad"]) ? (int)$row["Idespecialidad"] : null,
            ":especialidad"        => $row["especialidad"] ?? null,
            ":Fechaimpresion"      => !empty($row["Fechaimpresion"]) ? $row["Fechaimpresion"] : null,
            ":Estatus"             => isset($row["Estatus"]) && $row["Estatus"] !== "" ? (int)$row["Estatus"] : 0,
            ":FechaToma"           => !empty($row["FechaToma"]) ? $row["FechaToma"] : null,
            ":conciliado"          => !empty($row["conciliado"]) ? (int)$row["conciliado"] : null,
            ":origenimpresion"     => $row["origenimpresion"] ?? null,
            ":fechaestudio"        => !empty($row["fechaestudio"]) ? $row["fechaestudio"] : null,
            ":idconsulta"          => !empty($row["idconsulta"]) ? (int)$row["idconsulta"] : null,
            ":idlaboratorio"       => isset($row["idlaboratorio"]) && $row["idlaboratorio"] !== "" ? (int)$row["idlaboratorio"] : 0,
            ":urgente"             => isset($row["urgente"]) && $row["urgente"] !== "" ? (int)$row["urgente"] : 0,
            ":CVEIMPRESION"        => !empty($row["CVEIMPRESION"]) ? (int)$row["CVEIMPRESION"] : null,
            ":fechadescarga"       => !empty($row["fechadescarga"]) ? $row["fechadescarga"] : null
        ]);
    }

    $sqlSelect = "SELECT
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
                  WHERE idpersonal = :idpersonal
                  ORDER BY fechaestudio DESC, idestudio DESC";

    $stmtSelect = $conexion->prepare($sqlSelect);
    $stmtSelect->execute([
        ":idpersonal" => $idpersonal
    ]);

    $resultados = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);

    $conexion->commit();

    $salida = [];

    foreach ($resultados as $row) {
        $fechatoma       = !empty($row["FechaToma"]) ? date("d/m/Y", strtotime($row["FechaToma"])) : "";
        $fechainicio     = !empty($row["FechainicioVigencia"]) ? date("d/m/Y", strtotime($row["FechainicioVigencia"])) : "";
        $fechafinal      = !empty($row["FechaFinvigencia"]) ? date("d/m/Y", strtotime($row["FechaFinvigencia"])) : "";
        $fechaprobable   = !empty($row["FechaProbconsulta"]) ? date("d/m/Y", strtotime($row["FechaProbconsulta"])) : "";
        $fechaentrega    = !empty($row["Fechaimpresion"]) ? date("d/m/Y", strtotime($row["Fechaimpresion"])) : "";

        $fechainiciotime = !empty($row["FechainicioVigencia"]) ? strtotime($row["FechainicioVigencia"]) : null;
        $fechafinaltime  = !empty($row["FechaFinvigencia"]) ? strtotime($row["FechaFinvigencia"]) : null;
        $hoytime         = strtotime(date("Y-m-d"));

        $fechafinal_mas_un_dia = "";
        if (!empty($row["FechaFinvigencia"])) {
            $tmp = new DateTime($row["FechaFinvigencia"]);
            $tmp->modify("+1 day");
            $fechafinal_mas_un_dia = $tmp->format("d/m/Y");
        }

        $estatus = isset($row["Estatus"]) ? (int)$row["Estatus"] : 0;
        $urgente = isset($row["urgente"]) ? (int)$row["urgente"] : 0;
        $idlaboratorio = isset($row["idlaboratorio"]) ? (int)$row["idlaboratorio"] : 0;
        $origenimpresion = isset($row["origenimpresion"]) ? trim((string)$row["origenimpresion"]) : "";

        $hint = "";
        $accion_boton = "";
        $puede_descargar = false;

        if ($estatus !== 0) {
            $hint = "El pase para la realización de estudios fue cancelado";
            $accion_boton = "Cancelado";
        } elseif ($urgente === 1 && empty($row["Fechaimpresion"])) {
            $hint = "Imprimir este pase.";
            $accion_boton = "Descargar";
            $puede_descargar = true;
        } elseif (!empty($row["FechaToma"])) {
            $hint = "Los estudios se realizaron el " . $fechatoma;
            $accion_boton = "Realizados";
        } elseif (empty($row["Fechaconsulta"])) {
            $hint = "Sin Fecha de Consulta, deberá de agendar fecha de consulta antes del " . $fechaprobable;
            $accion_boton = "SinConsulta";
        } elseif (
            empty($fechainiciotime) ||
            empty($fechafinaltime) ||
            !(($hoytime >= $fechainiciotime) && ($hoytime <= $fechafinaltime))
        ) {
            $hint = "Imprimir en el periodo que inicia el " . $fechainicio . " y termina el día " . $fechafinal;
            $accion_boton = "FueraPeriodo";
        } elseif (!empty($row["Fechaimpresion"]) && strtoupper($origenimpresion) === "WEB") {
            $hint = "Reimprimir pase para estudios clínicos. El pase fue impreso y entregado el día: " . $fechaentrega;
            $accion_boton = "Reimprimir";
            $puede_descargar = true;
        } elseif ($idlaboratorio === 0) {
            $hint = "Antes de imprimir seleccione el laboratorio.";
            $accion_boton = "SinLaboratorio";
        } else {
            $hint = "Imprimir este pase antes del " . $fechafinal_mas_un_dia;
            $accion_boton = "Descargar";
            $puede_descargar = true;
        }

        $row["hint"] = $hint;
        $row["accion_boton"] = $accion_boton;
        $row["puede_descargar"] = $puede_descargar;

        $salida[] = $row;
    }

    app_json_response(200, [
        "success" => true,
        "total" => count($salida),
        "data" => $salida
    ]);

} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    app_json_response(500, [
        "success" => false,
        "message" => "Error al obtener los estudios clínicos."
    ]);
}