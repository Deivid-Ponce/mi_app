<?php
require_once "session_check.php";



if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    app_json_response(405, [
        "success" => false,
        "message" => "Método no permitido"
    ]);
}

try {
    
    registrar_auditoria("LOGOUT", "Usuario cerró sesión");

    borrar_remember_token();

    $_SESSION = [];

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_unset();
        session_destroy();
    }

    setcookie(session_name(), "", [
        "expires" => time() - 3600,
        "path" => "/",
        "secure" => app_es_https(),
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    app_json_response(200, [
        "success" => true,
        "message" => "Sesión cerrada correctamente"
    ]);

} catch (Throwable $e) {
    app_json_response(500, [
        "success" => false,
        "message" => "Error al cerrar sesión"
    ]);
}