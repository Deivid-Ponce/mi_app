<?php
require_once "session_check.php";

/* =========================================================
   validar_sesion.php - PRODUCCIÓN FINAL
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "GET" && $_SERVER["REQUEST_METHOD"] !== "POST") {
    app_json_response(405, [
        "success" => false,
        "message" => "Método no permitido"
    ]);
}

/*
   Tomar device_id del request o de sesión
*/
$deviceId = trim($_GET["device_id"] ?? $_POST["device_id"] ?? "");

if ($deviceId === "" && !empty($_SESSION["device_id"])) {
    $deviceId = trim($_SESSION["device_id"]);
}

if ($deviceId === "") {
    app_json_response(400, [
        "success" => false,
        "message" => "No se pudo identificar el dispositivo."
    ]);
}

/*
   Validar sesión
*/
if (!usuario_autenticado($deviceId)) {
    app_json_response(401, [
        "success" => false,
        "message" => "Sesión no válida"
    ]);
}

/*
   Validar dispositivo
*/
if (!validar_sesion_dispositivo()) {
    app_json_response(401, [
        "success" => false,
        "message" => "Dispositivo no autorizado"
    ]);
}

/*
   Respuesta limpia (SIN debug)
*/
app_json_response(200, [
    "success" => true,
    "usuario" => get_usuario_sesion()
]);