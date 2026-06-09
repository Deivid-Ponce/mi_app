<?php
require_once "conexion.php";
require_once "session_check.php";

require_login_json();

$login = trim($_SESSION["login"] ?? "");

if ($login === "") {
    app_json_response(401, [
        "success" => false,
        "message" => "Sesión no válida"
    ]);
}

try {
    $sql = "SELECT
                IdPersonal,
                NumCredencial,
                Nombre,
                Appaterno,
                ApMaterno,
                FechaNacimiento,
                CURP,
                LugarNacimiento
            FROM persona
            WHERE NumCredencial = :login
            LIMIT 1";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([
        ":login" => $login
    ]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        app_json_response(404, [
            "success" => false,
            "message" => "No se encontró información del usuario"
        ]);
    }

    app_json_response(200, [
        "success" => true,
        "data" => $usuario
    ]);

} catch (Throwable $e) {
    app_json_response(500, [
        "success" => false,
        "message" => "Error al obtener la información del usuario"
    ]);
}