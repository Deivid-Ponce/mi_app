<?php
require_once "conexion.php";
require_once "session_check.php";

require_login_json();

$idPersonal = (int)($_SESSION["idPersonal"] ?? 0);

if ($idPersonal <= 0) {
    app_json_response(401, [
        "success" => false,
        "message" => "Sesión no válida."
    ]);
}

try {
    $sql = "SELECT 
                Fechaaplicacion,
                anio,
                quincena,
                descripcion,
                cargo,
                abono,
                saldo,
                idmovimiento,
                idpersonal,
                id
            FROM webestadocuentaca
            WHERE idpersonal = :idpersonal
            ORDER BY Fechaaplicacion DESC, id DESC";

    $stmt = $conexion->prepare($sql);
    $stmt->bindValue(":idpersonal", $idPersonal, PDO::PARAM_INT);
    $stmt->execute();

    $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    app_json_response(200, [
        "success" => true,
        "data" => $movimientos
    ]);

} catch (Throwable $e) {
    app_json_response(500, [
        "success" => false,
        "message" => "Error al obtener el estado de cuenta."
    ]);
}