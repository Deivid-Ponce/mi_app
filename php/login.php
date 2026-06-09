<?php
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Content-Type-Options: nosniff");

require_once "session_check.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    app_json_response(405, [
        "success" => false,
        "message" => "Método no permitido"
    ]);
}

$usuario = trim($_POST["usuario"] ?? "");
$password = trim($_POST["password"] ?? "");
$recordarme = ($_POST["recordarme"] ?? "0") === "1";
$deviceId = trim($_POST["device_id"] ?? "");
$nombreDispositivo = trim($_POST["nombre_dispositivo"] ?? "Android WebView");

$ip = $_SERVER["HTTP_X_FORWARDED_FOR"] ?? $_SERVER["REMOTE_ADDR"] ?? "unknown";
$ip = trim(explode(",", $ip)[0]);

if ($usuario === "" || $password === "") {
    app_json_response(400, [
        "success" => false,
        "message" => "Completa usuario y contraseña"
    ]);
}

if ($deviceId === "") {
    app_json_response(400, [
        "success" => false,
        "message" => "No se pudo identificar el dispositivo."
    ]);
}

function registrar_intento_login($login, $ip, $deviceId, $success) {
    global $conexion;

    try {
        $stmt = $conexion->prepare("
            INSERT INTO login_attempts (login, ip, device_id, success, created_at)
            VALUES (:login, :ip, :device_id, :success, NOW())
        ");

        $stmt->execute([
            ":login" => $login,
            ":ip" => $ip,
            ":device_id" => $deviceId,
            ":success" => $success ? 1 : 0
        ]);
    } catch (Throwable $e) {}
}

function login_bloqueado($login, $ip) {
    global $conexion;

    try {
        $stmt = $conexion->prepare("
            SELECT COUNT(*) AS total
            FROM login_attempts
            WHERE success = 0
              AND created_at >= (NOW() - INTERVAL 10 MINUTE)
              AND (login = :login OR ip = :ip)
        ");

        $stmt->execute([
            ":login" => $login,
            ":ip" => $ip
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return ((int)($row["total"] ?? 0)) >= 5;

    } catch (Throwable $e) {
        return false;
    }
}

function password_es_md5($hash) {
    return is_string($hash) && preg_match('/^[a-f0-9]{32}$/i', $hash);
}

try {
    if (login_bloqueado($usuario, $ip)) {
        registrar_auditoria("LOGIN_BLOCK", "Usuario bloqueado por intentos fallidos");

        app_json_response(429, [
            "success" => false,
            "message" => "Demasiados intentos. Intenta nuevamente en unos minutos."
        ]);
    }

    $stmt = $conexion->prepare("
        SELECT 
            login,
            idPersonal,
            name,
            email,
            role,
            foto,
            active,
            pswd
        FROM sec_users
        WHERE login = :login
        LIMIT 1
    ");

    $stmt->execute([
        ":login" => $usuario
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || ($user["active"] ?? "") !== "Y") {
        registrar_intento_login($usuario, $ip, $deviceId, false);
        registrar_auditoria("LOGIN_FAIL", "Usuario inexistente o inactivo");

        app_json_response(401, [
            "success" => false,
            "message" => "Usuario, contraseña incorrecta o inactivo"
        ]);
    }

    $hashGuardado = (string)($user["pswd"] ?? "");
    $passwordCorrecta = false;
    $migrarPassword = false;

    if (password_es_md5($hashGuardado)) {
        if (hash_equals(strtolower($hashGuardado), md5($password))) {
            $passwordCorrecta = true;
            $migrarPassword = true;
        }
    } else {
        if (password_verify($password, $hashGuardado)) {
            $passwordCorrecta = true;

            if (password_needs_rehash($hashGuardado, PASSWORD_DEFAULT)) {
                $migrarPassword = true;
            }
        }
    }

    if (!$passwordCorrecta) {
        registrar_intento_login($usuario, $ip, $deviceId, false);
        registrar_auditoria("LOGIN_FAIL", "Contraseña incorrecta");

        app_json_response(401, [
            "success" => false,
            "message" => "Usuario, contraseña incorrecta o inactivo"
        ]);
    }

    if ($migrarPassword) {
        $nuevoHash = password_hash($password, PASSWORD_DEFAULT);

        $stmtUpdate = $conexion->prepare("
            UPDATE sec_users
            SET pswd = :pswd
            WHERE login = :login
            LIMIT 1
        ");

        $stmtUpdate->execute([
            ":pswd" => $nuevoHash,
            ":login" => $user["login"]
        ]);
    }

    $validacionDispositivo = validar_o_vincular_dispositivo(
        $user["idPersonal"],
        $user["login"],
        $deviceId,
        $nombreDispositivo
    );

    if (!$validacionDispositivo["success"]) {
        registrar_intento_login($usuario, $ip, $deviceId, false);
        registrar_auditoria("LOGIN_FAIL", "Dispositivo no autorizado");

        app_json_response(403, [
            "success" => false,
            "message" => $validacionDispositivo["message"]
        ]);
    }

    session_regenerate_id(true);

    $_SESSION["login"] = $user["login"];
    $_SESSION["idPersonal"] = $user["idPersonal"];
    $_SESSION["name"] = $user["name"];
    $_SESSION["email"] = $user["email"];
    $_SESSION["role"] = $user["role"];
    $_SESSION["foto"] = $user["foto"];
    $_SESSION["recordarme"] = $recordarme ? 1 : 0;
    $_SESSION["device_id"] = $deviceId;
    $_SESSION["CREATED"] = time();
    $_SESSION["LAST_ACTIVITY"] = time();

    if ($recordarme) {
        crear_remember_token((int)$user["idPersonal"]);
    } else {
        borrar_remember_token();
    }

    registrar_intento_login($usuario, $ip, $deviceId, true);

    // 🔥 AUDITORÍA LOGIN CORRECTO
    registrar_auditoria("LOGIN_OK", "Inicio de sesión correcto");

    session_write_close();

    app_json_response(200, [
        "success" => true,
        "message" => "Login correcto",
        "usuario" => [
            "login" => $user["login"],
            "idPersonal" => $user["idPersonal"],
            "nombre" => $user["name"] ?: $user["login"],
            "correo" => $user["email"] ?: "",
            "rol" => $user["role"] ?: "",
            "foto" => $user["foto"] ?: ""
        ]
    ]);

} catch (Throwable $e) {
    app_json_response(500, [
        "success" => false,
        "message" => "Error en servidor"
    ]);
}