<?php

/* =========================================================
   session_check.php - PRODUCCIÓN WEBVIEW
========================================================= */

if (session_status() === PHP_SESSION_NONE) {

    $https = (
        (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ||
        (!empty($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] === "https")
    );

    ini_set("session.use_strict_mode", "1");
    ini_set("session.use_only_cookies", "1");
    ini_set("session.cookie_httponly", "1");
    ini_set("session.cookie_secure", $https ? "1" : "0");
    ini_set("session.gc_maxlifetime", (string)(60 * 60 * 24 * 7));

    session_set_cookie_params([
        "lifetime" => 60 * 60 * 24 * 7,
        "path" => "/",
        "domain" => "",
        "secure" => $https,
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}

require_once __DIR__ . "/conexion.php";

/* =========================================================
   HELPERS
========================================================= */

function app_es_https() {
    return (
        (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ||
        (!empty($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] === "https")
    );
}

function app_json_response($statusCode, $data) {
    if (!headers_sent()) {
        header("Content-Type: application/json; charset=UTF-8");
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        header("X-Content-Type-Options: nosniff");
        http_response_code($statusCode);
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function obtener_device_id_request() {
    $deviceId = trim($_GET["device_id"] ?? $_POST["device_id"] ?? "");

    if ($deviceId !== "") {
        return $deviceId;
    }

    $raw = file_get_contents("php://input");
    if (!empty($raw)) {
        $json = json_decode($raw, true);
        if (is_array($json) && !empty($json["device_id"])) {
            return trim((string)$json["device_id"]);
        }
    }

    return "";
}

function cerrar_sesion_local() {
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
}

/* =========================================================
   PROTECCIÓN DE SESIÓN
========================================================= */

function proteger_sesion() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }

    $timeout = 60 * 60 * 8;

    if (isset($_SESSION["LAST_ACTIVITY"]) && (time() - $_SESSION["LAST_ACTIVITY"]) > $timeout) {
        borrar_remember_token();
        cerrar_sesion_local();
        return false;
    }

    $_SESSION["LAST_ACTIVITY"] = time();

    if (empty($_SESSION["CREATED"])) {
        $_SESSION["CREATED"] = time();
    } elseif ((time() - $_SESSION["CREATED"]) > 600) {
        session_regenerate_id(true);
        $_SESSION["CREATED"] = time();
    }

    return true;
}

/* =========================================================
   AUTENTICACIÓN GENERAL
========================================================= */

function usuario_autenticado($deviceId = "") {
    if (!proteger_sesion()) {
        return false;
    }

    if (!empty($_SESSION["login"])) {
        return true;
    }

    if ($deviceId === "") {
        $deviceId = obtener_device_id_request();
    }

    return intentar_login_por_remember_token($deviceId);
}

/* =========================================================
   DISPOSITIVOS
========================================================= */

function validar_o_vincular_dispositivo($idPersonal, $login, $deviceId, $nombreDispositivo = "") {
    global $conexion;

    $idPersonal = (int)$idPersonal;
    $login = trim((string)$login);
    $deviceId = trim((string)$deviceId);
    $nombreDispositivo = trim((string)$nombreDispositivo);

    if ($idPersonal <= 0 || $login === "" || $deviceId === "") {
        return [
            "success" => false,
            "message" => "No se pudo identificar el dispositivo."
        ];
    }

    try {
        $conexion->beginTransaction();

        $stmtDevice = $conexion->prepare("
            SELECT *
            FROM usuarios_dispositivos
            WHERE device_id = :device_id
            LIMIT 1
        ");

        $stmtDevice->execute([
            ":device_id" => $deviceId
        ]);

        $deviceRow = $stmtDevice->fetch(PDO::FETCH_ASSOC);

        if ($deviceRow) {
            if ((int)$deviceRow["idPersonal"] !== $idPersonal && $deviceRow["estatus"] !== "LIBERADO") {
                $conexion->rollBack();

                return [
                    "success" => false,
                    "message" => "Este dispositivo ya está vinculado a otra cuenta."
                ];
            }

            if ((int)$deviceRow["idPersonal"] === $idPersonal && $deviceRow["estatus"] === "BLOQUEADO") {
                $conexion->rollBack();

                return [
                    "success" => false,
                    "message" => "Este dispositivo fue bloqueado por seguridad."
                ];
            }
        }

        $stmtActivoUsuario = $conexion->prepare("
            SELECT *
            FROM usuarios_dispositivos
            WHERE idPersonal = :idPersonal
              AND estatus = 'ACTIVO'
            LIMIT 1
        ");

        $stmtActivoUsuario->execute([
            ":idPersonal" => $idPersonal
        ]);

        $activoUsuario = $stmtActivoUsuario->fetch(PDO::FETCH_ASSOC);

        if ($activoUsuario) {
            if ($activoUsuario["device_id"] !== $deviceId) {
                $conexion->rollBack();

                return [
                    "success" => false,
                    "message" => "Tu cuenta ya está vinculada a otro dispositivo."
                ];
            }

            $update = $conexion->prepare("
                UPDATE usuarios_dispositivos
                SET fecha_ultimo_acceso = NOW(),
                    nombre_dispositivo = CASE
                        WHEN :nombre_dispositivo <> '' THEN :nombre_dispositivo
                        ELSE nombre_dispositivo
                    END,
                    user_agent = :user_agent
                WHERE id = :id
            ");

            $update->execute([
                ":nombre_dispositivo" => $nombreDispositivo,
                ":user_agent" => $_SERVER["HTTP_USER_AGENT"] ?? "",
                ":id" => $activoUsuario["id"]
            ]);

            $conexion->commit();

            return [
                "success" => true,
                "message" => "Dispositivo validado."
            ];
        }

        if ($deviceRow && $deviceRow["estatus"] === "LIBERADO") {
            $update = $conexion->prepare("
                UPDATE usuarios_dispositivos
                SET idPersonal = :idPersonal,
                    login = :login,
                    nombre_dispositivo = :nombre_dispositivo,
                    estatus = 'ACTIVO',
                    fecha_vinculacion = NOW(),
                    fecha_ultimo_acceso = NOW(),
                    fecha_bloqueo = NULL,
                    fecha_liberacion = NULL,
                    motivo_bloqueo = NULL,
                    user_agent = :user_agent
                WHERE id = :id
            ");

            $update->execute([
                ":idPersonal" => $idPersonal,
                ":login" => $login,
                ":nombre_dispositivo" => $nombreDispositivo,
                ":user_agent" => $_SERVER["HTTP_USER_AGENT"] ?? "",
                ":id" => $deviceRow["id"]
            ]);

            $conexion->commit();

            return [
                "success" => true,
                "message" => "Dispositivo vinculado correctamente."
            ];
        }

        $insert = $conexion->prepare("
            INSERT INTO usuarios_dispositivos
            (
                idPersonal,
                login,
                device_id,
                nombre_dispositivo,
                estatus,
                fecha_vinculacion,
                fecha_ultimo_acceso,
                user_agent
            )
            VALUES
            (
                :idPersonal,
                :login,
                :device_id,
                :nombre_dispositivo,
                'ACTIVO',
                NOW(),
                NOW(),
                :user_agent
            )
        ");

        $insert->execute([
            ":idPersonal" => $idPersonal,
            ":login" => $login,
            ":device_id" => $deviceId,
            ":nombre_dispositivo" => $nombreDispositivo,
            ":user_agent" => $_SERVER["HTTP_USER_AGENT"] ?? ""
        ]);

        $conexion->commit();

        return [
            "success" => true,
            "message" => "Dispositivo vinculado correctamente."
        ];

    } catch (Throwable $e) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }

        return [
            "success" => false,
            "message" => "Error validando dispositivo."
        ];
    }
}

function validar_sesion_dispositivo() {
    global $conexion;

    if (
        empty($_SESSION["login"]) ||
        empty($_SESSION["idPersonal"]) ||
        empty($_SESSION["device_id"])
    ) {
        return false;
    }

    try {
        $stmt = $conexion->prepare("
            SELECT id
            FROM usuarios_dispositivos
            WHERE idPersonal = :idPersonal
              AND login = :login
              AND device_id = :device_id
              AND estatus = 'ACTIVO'
            LIMIT 1
        ");

        $stmt->execute([
            ":idPersonal" => $_SESSION["idPersonal"],
            ":login" => $_SESSION["login"],
            ":device_id" => $_SESSION["device_id"]
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return false;
        }

        $update = $conexion->prepare("
            UPDATE usuarios_dispositivos
            SET fecha_ultimo_acceso = NOW()
            WHERE id = :id
        ");

        $update->execute([
            ":id" => $row["id"]
        ]);

        return true;

    } catch (Throwable $e) {
        return false;
    }
}

/* =========================================================
   REMEMBER TOKEN
========================================================= */

function intentar_login_por_remember_token($deviceId = "") {
    global $conexion;

    if (empty($_COOKIE["remember_token"])) {
        return false;
    }

    $deviceId = trim((string)$deviceId);

    if ($deviceId === "") {
        return false;
    }

    $partes = explode(":", $_COOKIE["remember_token"]);

    if (count($partes) !== 2) {
        borrar_cookie_remember();
        return false;
    }

    $selector = $partes[0];
    $token = $partes[1];

    if (!preg_match('/^[a-f0-9]{32}$/i', $selector) || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
        borrar_cookie_remember();
        return false;
    }

    try {
        $stmt = $conexion->prepare("
            SELECT 
                rt.idPersonal,
                rt.selector,
                rt.token_hash,
                rt.expires_at,
                u.login,
                u.name,
                u.email,
                u.role,
                u.foto,
                u.active
            FROM usuarios_remember_tokens rt
            INNER JOIN sec_users u ON u.idPersonal = rt.idPersonal
            WHERE rt.selector = :selector
            LIMIT 1
        ");

        $stmt->execute([
            ":selector" => $selector
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            borrar_cookie_remember();
            return false;
        }

        if ($row["active"] !== "Y") {
            borrar_remember_token();
            return false;
        }

        if (strtotime($row["expires_at"]) < time()) {
            borrar_remember_token();
            return false;
        }

        $tokenHash = hash("sha256", $token);

        if (!hash_equals($row["token_hash"], $tokenHash)) {
            borrar_remember_token();
            return false;
        }

        $validacionDispositivo = validar_o_vincular_dispositivo(
            $row["idPersonal"],
            $row["login"],
            $deviceId,
            "Android WebView"
        );

        if (!$validacionDispositivo["success"]) {
            return false;
        }

        session_regenerate_id(true);

        $_SESSION["login"] = $row["login"];
        $_SESSION["idPersonal"] = $row["idPersonal"];
        $_SESSION["name"] = $row["name"];
        $_SESSION["email"] = $row["email"];
        $_SESSION["role"] = $row["role"];
        $_SESSION["foto"] = $row["foto"];
        $_SESSION["recordarme"] = 1;
        $_SESSION["device_id"] = $deviceId;
        $_SESSION["CREATED"] = time();
        $_SESSION["LAST_ACTIVITY"] = time();

        $update = $conexion->prepare("
            UPDATE usuarios_remember_tokens
            SET last_used_at = NOW()
            WHERE selector = :selector
        ");

        $update->execute([
            ":selector" => $selector
        ]);

        return true;

    } catch (Throwable $e) {
        return false;
    }
}

function crear_remember_token($idPersonal) {
    global $conexion;

    $idPersonal = (int)$idPersonal;

    if ($idPersonal <= 0) {
        return false;
    }

    try {
        $selector = bin2hex(random_bytes(16));
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash("sha256", $token);
        $expiresAt = date("Y-m-d H:i:s", time() + (60 * 60 * 24 * 30));

        $stmtDelete = $conexion->prepare("
            DELETE FROM usuarios_remember_tokens
            WHERE idPersonal = :idPersonal
        ");

        $stmtDelete->execute([
            ":idPersonal" => $idPersonal
        ]);

        $stmt = $conexion->prepare("
            INSERT INTO usuarios_remember_tokens
            (idPersonal, selector, token_hash, expires_at, created_at, last_used_at)
            VALUES (:idPersonal, :selector, :token_hash, :expires_at, NOW(), NULL)
        ");

        $stmt->execute([
            ":idPersonal" => $idPersonal,
            ":selector" => $selector,
            ":token_hash" => $tokenHash,
            ":expires_at" => $expiresAt
        ]);

        setcookie("remember_token", $selector . ":" . $token, [
            "expires" => time() + (60 * 60 * 24 * 30),
            "path" => "/",
            "secure" => app_es_https(),
            "httponly" => true,
            "samesite" => "Lax"
        ]);

        return true;

    } catch (Throwable $e) {
        return false;
    }
}

function borrar_cookie_remember() {
    setcookie("remember_token", "", [
        "expires" => time() - 3600,
        "path" => "/",
        "secure" => app_es_https(),
        "httponly" => true,
        "samesite" => "Lax"
    ]);
}

function borrar_remember_token() {
    global $conexion;

    if (!empty($_COOKIE["remember_token"])) {
        $partes = explode(":", $_COOKIE["remember_token"]);

        if (count($partes) === 2) {
            $selector = $partes[0];

            try {
                $stmt = $conexion->prepare("
                    DELETE FROM usuarios_remember_tokens
                    WHERE selector = :selector
                ");

                $stmt->execute([
                    ":selector" => $selector
                ]);
            } catch (Throwable $e) {
            }
        }
    }

    borrar_cookie_remember();
}

/* =========================================================
   USUARIO EN SESIÓN
========================================================= */

function get_usuario_sesion() {
    return [
        "login" => $_SESSION["login"] ?? "",
        "idPersonal" => $_SESSION["idPersonal"] ?? "",
        "nombre" => $_SESSION["name"] ?? ($_SESSION["login"] ?? ""),
        "correo" => $_SESSION["email"] ?? "",
        "rol" => $_SESSION["role"] ?? "",
        "foto" => $_SESSION["foto"] ?? ""
    ];
}

/* =========================================================
   AUDITORÍA
========================================================= */

function registrar_auditoria($accion, $detalle = "") {
    global $conexion;

    try {
        $login = $_SESSION["login"] ?? null;
        $idpersonal = $_SESSION["idPersonal"] ?? null;
        $deviceId = $_SESSION["device_id"] ?? obtener_device_id_request();

        $ip = $_SERVER["HTTP_X_FORWARDED_FOR"] ?? $_SERVER["REMOTE_ADDR"] ?? "";
        $ip = trim(explode(",", $ip)[0]);

        $stmt = $conexion->prepare("
            INSERT INTO auditoria_app
            (
                login,
                idpersonal,
                accion,
                detalle,
                ip,
                device_id,
                user_agent
            )
            VALUES
            (
                :login,
                :idpersonal,
                :accion,
                :detalle,
                :ip,
                :device_id,
                :user_agent
            )
        ");

        $stmt->execute([
            ":login" => $login,
            ":idpersonal" => $idpersonal,
            ":accion" => trim((string)$accion),
            ":detalle" => trim((string)$detalle),
            ":ip" => $ip,
            ":device_id" => $deviceId,
            ":user_agent" => $_SERVER["HTTP_USER_AGENT"] ?? ""
        ]);

        return true;

    } catch (Throwable $e) {
        return false;
    }
}

/* =========================================================
   GUARD PRINCIPAL PARA APIs JSON
========================================================= */

function require_login_json() {
    $deviceId = $_SESSION["device_id"] ?? obtener_device_id_request();

    if (!usuario_autenticado($deviceId) || !validar_sesion_dispositivo()) {
        app_json_response(401, [
            "success" => false,
            "message" => "Sesión no válida o dispositivo no autorizado"
        ]);
    }
}