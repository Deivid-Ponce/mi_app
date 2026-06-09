<?php
require_once "conexion.php";
require_once "session_check.php";

require_login_json();

$numCredencial = trim($_SESSION["login"] ?? "");
$idreceta = isset($_GET["idreceta"]) ? (int)$_GET["idreceta"] : 0;

if ($numCredencial === "") {
    app_json_response(401, [
        "success" => false,
        "message" => "Sesión no válida."
    ]);
}

if ($idreceta <= 0) {
    app_json_response(400, [
        "success" => false,
        "message" => "No se recibió un id de receta válido."
    ]);
}

try {
    $sql = "
        SELECT
            id,
            idreceta,
            medicamento,
            presentacion,
            posologia,
            diagnostico,
            medico,
            especialidad,
            fechareceta,
            cantidad,
            nombre,
            NumCredencial
        FROM webrecetamedicamentodetalle
        WHERE NumCredencial = :NumCredencial
          AND idreceta = :idreceta
        ORDER BY id ASC
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->bindValue(":NumCredencial", $numCredencial, PDO::PARAM_STR);
    $stmt->bindValue(":idreceta", $idreceta, PDO::PARAM_INT);
    $stmt->execute();

    $detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

    app_json_response(200, [
        "success" => true,
        "data" => $detalle
    ]);

} catch (Throwable $e) {
    app_json_response(500, [
        "success" => false,
        "message" => "Error al obtener el detalle de la receta."
    ]);
}