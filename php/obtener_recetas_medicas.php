<?php
require_once "conexion.php";
require_once "session_check.php";

require_login_json();

$numCredencial = trim($_SESSION["login"] ?? "");

if ($numCredencial === "") {
    app_json_response(401, [
        "success" => false,
        "message" => "Sesión no válida."
    ]);
}

try {
    $sql = "
        SELECT 
            idreceta,
            MAX(medico) AS medico,
            MAX(nombre) AS nombre,
            MAX(especialidad) AS especialidad,
            MAX(fechareceta) AS fechareceta
        FROM webrecetamedicamentodetalle
        WHERE NumCredencial = :NumCredencial
          AND idreceta IS NOT NULL
        GROUP BY idreceta
        ORDER BY MAX(fechareceta) DESC, idreceta DESC
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->bindValue(":NumCredencial", $numCredencial, PDO::PARAM_STR);
    $stmt->execute();

    $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    app_json_response(200, [
        "success" => true,
        "data" => $recetas
    ]);

} catch (Throwable $e) {
    app_json_response(500, [
        "success" => false,
        "message" => "Error al obtener las recetas médicas."
    ]);
}