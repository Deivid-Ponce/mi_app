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
                Relacion,
                FechaVigencia,
                IdCausaBaja
            FROM persona
            WHERE NumCredencial = :login
              AND IdCausaBaja NOT IN (2, 4, 5)
              AND (
                    FechaVigencia >= CURDATE()
                    OR FechaVigencia IS NULL
                  )
            ORDER BY
                CASE
                    WHEN Relacion = 'TITULAR' THEN 0
                    ELSE 1
                END,
                Nombre ASC";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([
        ":login" => $login
    ]);

    $personas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    app_json_response(200, [
        "success" => true,
        "data" => $personas
    ]);

} catch (Throwable $e) {
    app_json_response(500, [
        "success" => false,
        "message" => "Error al obtener la información de organización"
    ]);
}