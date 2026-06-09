<?php
require_once "conexion.php";
require_once "session_check.php";

require_login_json();

$login = trim($_SESSION["login"] ?? "");
$nombre = trim($_SESSION["name"] ?? "");

if ($login === "") {
    app_json_response(401, [
        "success" => false,
        "message" => "Sesión no válida."
    ]);
}

try {
    $sql = "SELECT
                idpersonal,
                idcuenta,
                saldofloat,
                fechaAplicacion,
                idmovimiento,
                FechaActualizacion,
                aportacion,
                numcredencial,
                quincena,
                anio,
                montoprestamo,
                plazo,
                quincena_final,
                saldo_prestamo,
                aniofinal,
                quincenainicial,
                anioinicial,
                saldo,
                saldodouble,
                id
            FROM websaldocajaahorro
            WHERE numcredencial = :login
            ORDER BY
                FechaActualizacion DESC,
                fechaAplicacion DESC,
                id DESC
            LIMIT 1";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([
        ":login" => $login
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        app_json_response(404, [
            "success" => false,
            "message" => "No se encontró información de caja de ahorro."
        ]);
    }

    app_json_response(200, [
        "success" => true,
        "data" => [
            "nombre" => $nombre,
            "idpersonal" => $row["idpersonal"],
            "idcuenta" => $row["idcuenta"],
            "SaldoCaja" => $row["saldofloat"],
            "fechaAplicacion" => $row["fechaAplicacion"],
            "FechaActualizacion" => $row["FechaActualizacion"],
            "Aportacion" => $row["aportacion"],
            "numcredencial" => $row["numcredencial"],
            "qui_saldo" => $row["quincena"],
            "anio_saldo" => $row["anio"],
            "MontoPrestamo" => $row["montoprestamo"],
            "plazo" => $row["plazo"],
            "plazo2" => $row["plazo"],
            "QuinFin" => $row["quincena_final"],
            "SaldoPrestamo" => $row["saldo_prestamo"],
            "AnioFinal" => $row["aniofinal"],
            "QuinIni" => $row["quincenainicial"],
            "AnioIni" => $row["anioinicial"],
            "QuinAct" => $row["quincena"],
            "AnioAct" => $row["anio"]
        ]
    ]);

} catch (Throwable $e) {
    app_json_response(500, [
        "success" => false,
        "message" => "Error al obtener la información de caja de ahorro."
    ]);
}