<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . "/../api/db.php";

function bloquear(): void
{
    http_response_code(403);

    exit('Acceso denegado');
}

try {

    $token = trim($_GET['app_token'] ?? '');

    if ($token === '') {
        error_log("LOGIN_GATE: token vacío");
        bloquear();
    }

    if (strlen($token) !== 64) {
        error_log("LOGIN_GATE: token inválido");
        bloquear();
    }

    $tokenHash = hash('sha256', $token);

    $stmt = $pdo->prepare("
        SELECT 
            id,
            device_id,
            expires_at,
            usado
        FROM app_access_tokens
        WHERE token_hash = ?
        LIMIT 1
    ");

    $stmt->execute([$tokenHash]);

    $row = $stmt->fetch();

    if (!$row) {
        error_log("LOGIN_GATE: token no encontrado");
        bloquear();
    }

    if ((int)$row['usado'] === 1) {
        error_log("LOGIN_GATE: token reutilizado");
        bloquear();
    }

    if (strtotime($row['expires_at']) < time()) {
        error_log("LOGIN_GATE: token expirado");
        bloquear();
    }

    $update = $pdo->prepare("
        UPDATE app_access_tokens
        SET usado = 1
        WHERE id = ?
    ");

    $update->execute([$row['id']]);

    session_regenerate_id(true);

    $_SESSION['APP_WEBVIEW_OK'] = true;
    $_SESSION['APP_DEVICE_ID'] = $row['device_id'];
    $_SESSION['APP_AUTH_TIME'] = time();

    header("Location: login.html");
    exit;

} catch (Throwable $e) {

    error_log("LOGIN_GATE ERROR: " . $e->getMessage());

    bloquear();
}