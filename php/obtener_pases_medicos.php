<?php
require_once "conexion.php";
require_once "session_check.php";

require_login_json();

$login = trim($_SESSION["login"] ?? "");
$idpersonalSesion = (int)($_SESSION["idPersonal"] ?? 0);

if ($login === "" || $idpersonalSesion <= 0) {
    app_json_response(401, [
        "success" => false,
        "message" => "Sesión no válida."
    ]);
}

function valorFecha($valor) {
    if (empty($valor)) return null;
    $time = strtotime($valor);
    return $time ? date("Y-m-d H:i:s", $time) : null;
}

function valorInt($valor) {
    if ($valor === null || $valor === "") return null;
    return (int)$valor;
}

try {
    $config_api = require "/home/1548792.cloudwaysapps.com/bpnhfkwfyb/private_html/cloud_config_pasesmedicos.php";

    $base_url = $config_api["API_URL"];
    $token    = $config_api["API_TOKEN"];

    $url = $base_url . "?idpersonal=" . urlencode($idpersonalSesion) . "&token=" . urlencode($token);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        throw new Exception("Error conexión API");
    }

    curl_close($ch);

    $data = json_decode($response, true);

    if (!isset($data["success"]) || $data["success"] !== true) {
        throw new Exception("API inválida");
    }

    if (!isset($data["data"]) || !is_array($data["data"])) {
        throw new Exception("Sin datos");
    }

    $conexion->beginTransaction();

    $idsPase = [];

    foreach ($data["data"] as $row) {
        if (!empty($row["idpase"])) {
            $idsPase[] = (int)$row["idpase"];
        }
    }

    $idsPase = array_values(array_unique($idsPase));

    if (!empty($idsPase)) {
        $placeholders = implode(",", array_fill(0, count($idsPase), "?"));

        $sqlDelete = "DELETE FROM pasesmedicos_usuarios 
                      WHERE idpase IN ($placeholders)
                        AND TRIM(numcredencial) = ?";

        $stmtDelete = $conexion->prepare($sqlDelete);
        $params = array_merge($idsPase, [$login]);
        $stmtDelete->execute($params);
    }

    $sqlInsert = "INSERT INTO pasesmedicos_usuarios (
        idpersonal,
        idpase,
        numcredencial,
        numempleado,
        nombre,
        medico,
        FechainicioVigencia,
        Fechaconsulta,
        FechaFinvigencia,
        FechaProbconsulta,
        secretaria,
        cadenaFecha,
        Idespecialidad,
        especialidad,
        Fechaimpresion,
        Estatus,
        conciliado,
        origenimpresion,
        fechapase,
        idconsulta,
        idmedicopase,
        CVEIMPRESION,
        fechadescarga,
        observaciones,
        telefono,
        direccion,
        fechavisitamedico,
        horavisitamedico,
        fechacapturaSM,
        idconsultame,
        relacion
    ) VALUES (
        :idpersonal,
        :idpase,
        :numcredencial,
        :numempleado,
        :nombre,
        :medico,
        :FechainicioVigencia,
        :Fechaconsulta,
        :FechaFinvigencia,
        :FechaProbconsulta,
        :secretaria,
        :cadenaFecha,
        :Idespecialidad,
        :especialidad,
        :Fechaimpresion,
        :Estatus,
        :conciliado,
        :origenimpresion,
        :fechapase,
        :idconsulta,
        :idmedicopase,
        :CVEIMPRESION,
        :fechadescarga,
        :observaciones,
        :telefono,
        :direccion,
        :fechavisitamedico,
        :horavisitamedico,
        :fechacapturaSM,
        :idconsultame,
        :relacion
    )";

    $stmtInsert = $conexion->prepare($sqlInsert);

    foreach ($data["data"] as $row) {
        $idpersonalPaciente = valorInt($row["idpersonal"] ?? null);

        if ($idpersonalPaciente === null || $idpersonalPaciente <= 0) {
            $idpersonalPaciente = $idpersonalSesion;
        }

        $stmtInsert->execute([
            ":idpersonal" => $idpersonalPaciente,
            ":idpase" => valorInt($row["idpase"]),
            ":numcredencial" => $login,
            ":numempleado" => valorInt($row["numempleado"]),
            ":nombre" => $row["nombre"] ?? "",
            ":medico" => $row["medico"] ?? "",
            ":FechainicioVigencia" => valorFecha($row["FechainicioVigencia"] ?? null),
            ":Fechaconsulta" => valorFecha($row["Fechaconsulta"] ?? null),
            ":FechaFinvigencia" => valorFecha($row["FechaFinvigencia"] ?? null),
            ":FechaProbconsulta" => valorFecha($row["FechaProbconsulta"] ?? null),
            ":secretaria" => $row["secretaria"] ?? "",
            ":cadenaFecha" => $row["cadenaFecha"] ?? "",
            ":Idespecialidad" => valorInt($row["Idespecialidad"] ?? null),
            ":especialidad" => $row["especialidad"] ?? "",
            ":Fechaimpresion" => valorFecha($row["Fechaimpresion"] ?? null),
            ":Estatus" => valorInt($row["Estatus"] ?? null),
            ":conciliado" => valorInt($row["conciliado"] ?? null),
            ":origenimpresion" => $row["origenimpresion"] ?? "",
            ":fechapase" => valorFecha($row["fechapase"] ?? null),
            ":idconsulta" => valorInt($row["idconsulta"] ?? null),
            ":idmedicopase" => valorInt($row["idmedicopase"] ?? null),
            ":CVEIMPRESION" => valorInt($row["CVEIMPRESION"] ?? null),
            ":fechadescarga" => valorFecha($row["fechadescarga"] ?? null),
            ":observaciones" => $row["observaciones"] ?? "",
            ":telefono" => $row["telefono"] ?? "",
            ":direccion" => $row["direccion"] ?? "",
            ":fechavisitamedico" => valorFecha($row["fechavisitamedico"] ?? null),
            ":horavisitamedico" => valorFecha($row["horavisitamedico"] ?? null),
            ":fechacapturaSM" => valorFecha($row["fechacapturaSM"] ?? null),
            ":idconsultame" => valorInt($row["idconsultame"] ?? null),
            ":relacion" => $row["relacion"] ?? ""
        ]);
    }

    $conexion->commit();

    $sql = "SELECT 
                idpase,
                idpersonal,
                nombre,
                medico,
                FechainicioVigencia,
                FechaFinvigencia,
                origenimpresion,
                especialidad
            FROM pasesmedicos_usuarios
            WHERE TRIM(numcredencial) = TRIM(:login)
              AND CURDATE() BETWEEN DATE(FechainicioVigencia) AND DATE(FechaFinvigencia)
              AND (origenimpresion IS NULL OR origenimpresion = '')
            ORDER BY nombre ASC";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([
        ":login" => $login
    ]);

    $pases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    app_json_response(200, [
        "success" => true,
        "data" => $pases
    ]);

} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    app_json_response(500, [
        "success" => false,
        "message" => "Error al obtener pases médicos."
    ]);
}