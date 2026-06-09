<?php
require_once "conexion.php";
require_once "session_check.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    app_json_response(405, [
        "success" => false,
        "message" => "Método no permitido"
    ]);
}

require_login_json();

$login = trim($_SESSION["login"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = trim($_POST["password"] ?? "");

if ($login === "") {
    app_json_response(401, [
        "success" => false,
        "message" => "Sesión no válida."
    ]);
}

if ($email === "" && $password === "") {
    app_json_response(400, [
        "success" => false,
        "message" => "No hay datos para actualizar"
    ]);
}

if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    app_json_response(400, [
        "success" => false,
        "message" => "Correo no válido."
    ]);
}

if ($password !== "" && strlen($password) < 8) {
    app_json_response(400, [
        "success" => false,
        "message" => "La contraseña debe tener mínimo 8 caracteres."
    ]);
}

try {
    $campos = [];
    $params = [
        ":login" => $login
    ];

    if ($email !== "") {
        $campos[] = "email = :email";
        $params[":email"] = $email;
    }

    if ($password !== "") {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $campos[] = "pswd = :pswd";
        $params[":pswd"] = $passwordHash;
    }

    $sql = "UPDATE sec_users
            SET " . implode(", ", $campos) . "
            WHERE login = :login
            LIMIT 1";

    $stmt = $conexion->prepare($sql);
    $stmt->execute($params);

    // Actualizar sesión si cambió correo
    if ($email !== "") {
        $_SESSION["email"] = $email;
    }

    /*
      Sincronización externa
    */
    if ($password !== "" || $email !== "") {

        $configPath = "/home/1548792.cloudwaysapps.com/bpnhfkwfyb/private_html/suspeser_sync_config.php";

        if (file_exists($configPath)) {

            $config = require $configPath;

            $apiUrl = trim($config["API_URL"] ?? "");
            $apiToken = trim($config["API_TOKEN"] ?? "");

            if ($apiUrl !== "" && $apiToken !== "") {

                $payload = [
                    "NumCredencial" => $login,
                    "correo" => $email
                ];

                if ($password !== "") {
                    $payload["contrasena"] = $password;
                }

                $ch = curl_init($apiUrl);

                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => [
                        "Content-Type: application/json",
                        "X-API-TOKEN: " . $apiToken
                    ],
                    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);

                curl_close($ch);

                $apiData = json_decode($response, true);

                if ($curlError || $httpCode !== 200 || !$apiData || ($apiData["ok"] ?? false) !== true) {
                    app_json_response(500, [
                        "success" => false,
                        "message" => "Se actualizó en la nube, pero falló la sincronización con SUSPESER."
                    ]);
                }
            }
        }
    }

    /* =========================================================
       AUDITORÍA (SIN DATOS SENSIBLES)
    ========================================================= */

    if ($email !== "") {
        registrar_auditoria("CAMBIO_CORREO", "Usuario actualizó su correo");
    }

    if ($password !== "") {
        registrar_auditoria("CAMBIO_PASSWORD", "Usuario actualizó su contraseña");
    }

    /* =========================================================
       RESPUESTA FINAL
    ========================================================= */

    app_json_response(200, [
        "success" => true,
        "message" => (
            $email !== "" && $password !== ""
                ? "Correo y contraseña actualizados correctamente"
                : ($email !== ""
                    ? "Correo actualizado correctamente"
                    : "Contraseña actualizada correctamente")
        ),
        "usuario" => [
            "login" => $_SESSION["login"] ?? "",
            "idPersonal" => $_SESSION["idPersonal"] ?? "",
            "nombre" => $_SESSION["name"] ?? ($_SESSION["login"] ?? ""),
            "correo" => $_SESSION["email"] ?? "",
            "rol" => $_SESSION["role"] ?? "",
            "foto" => $_SESSION["foto"] ?? ""
        ]
    ]);

} catch (Throwable $e) {

    app_json_response(500, [
        "success" => false,
        "message" => "Error al actualizar los datos"
    ]);
}