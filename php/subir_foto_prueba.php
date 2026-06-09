<?php
header("Content-Type: application/json; charset=UTF-8");

require_once "conexion.php";
require_once "session_check.php";

require_login_json();

$login = trim($_SESSION["login"] ?? "");
$idPersonal = isset($_POST["idpersonal"]) ? (int)$_POST["idpersonal"] : 0;
$imagenBase64 = isset($_POST["imagen_base64"]) ? trim($_POST["imagen_base64"]) : "";

if ($login === "") {
    app_json_response(401, [
        "success" => false,
        "message" => "Sesión no válida."
    ]);
}

if ($idPersonal <= 0) {
    app_json_response(400, [
        "success" => false,
        "message" => "Solicitud inválida."
    ]);
}

if ($imagenBase64 === "") {
    app_json_response(400, [
        "success" => false,
        "message" => "No se pudo procesar la imagen."
    ]);
}

/* ============================
   VALIDAR PERTENENCIA
============================ */

$sql = "SELECT IdPersonal
        FROM persona
        WHERE IdPersonal = :idpersonal
          AND TRIM(NumCredencial) = TRIM(:login)
        LIMIT 1";

$stmt = $conexion->prepare($sql);
$stmt->execute([
    ":idpersonal" => $idPersonal,
    ":login" => $login
]);

$persona = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$persona) {
    app_json_response(403, [
        "success" => false,
        "message" => "No tienes permiso para realizar esta acción."
    ]);
}

/* ============================
   VALIDAR BASE64
============================ */

if (!preg_match("#^data:image/(jpeg|jpg|png);base64,#i", $imagenBase64)) {
    app_json_response(400, [
        "success" => false,
        "message" => "Formato de imagen no permitido."
    ]);
}

$imagenBase64 = preg_replace("#^data:image/\w+;base64,#i", "", $imagenBase64);
$imagenBase64 = str_replace(" ", "+", $imagenBase64);

$binario = base64_decode($imagenBase64, true);

if ($binario === false || strlen($binario) <= 0) {
    app_json_response(400, [
        "success" => false,
        "message" => "No se pudo procesar la imagen."
    ]);
}

/* Máximo 5 MB */
if (strlen($binario) > 5 * 1024 * 1024) {
    app_json_response(400, [
        "success" => false,
        "message" => "La imagen excede el tamaño permitido."
    ]);
}

/* Validar imagen real */
$infoImagen = @getimagesizefromstring($binario);

if ($infoImagen === false) {
    app_json_response(400, [
        "success" => false,
        "message" => "El archivo recibido no es una imagen válida."
    ]);
}

$mime = $infoImagen["mime"] ?? "";

if (!in_array($mime, ["image/jpeg", "image/png"], true)) {
    app_json_response(400, [
        "success" => false,
        "message" => "Formato de imagen no permitido."
    ]);
}

/* ============================
   GUARDAR FOTO EN RUTA REAL
============================ */

$carpetaDestino = $_SERVER["DOCUMENT_ROOT"] . "/app/_lib/file/img/fotos/";

if (!is_dir($carpetaDestino) || !is_writable($carpetaDestino)) {
    app_json_response(500, [
        "success" => false,
        "message" => "No se pudo completar la operación."
    ]);
}

$nombreArchivo = $idPersonal . ".jpg";
$rutaArchivo = $carpetaDestino . $nombreArchivo;

$imagen = @imagecreatefromstring($binario);

if (!$imagen) {
    app_json_response(400, [
        "success" => false,
        "message" => "No se pudo procesar la imagen."
    ]);
}

$ancho = imagesx($imagen);
$alto = imagesy($imagen);

$lienzo = imagecreatetruecolor($ancho, $alto);
$blanco = imagecolorallocate($lienzo, 255, 255, 255);

imagefill($lienzo, 0, 0, $blanco);
imagecopy($lienzo, $imagen, 0, 0, 0, 0, $ancho, $alto);

$guardado = imagejpeg($lienzo, $rutaArchivo, 90);

imagedestroy($imagen);
imagedestroy($lienzo);

clearstatcache(true, $rutaArchivo);

if (!$guardado || !file_exists($rutaArchivo)) {
    app_json_response(500, [
        "success" => false,
        "message" => "No se pudo completar la operación."
    ]);
}

chmod($rutaArchivo, 0644);

if (function_exists("registrar_auditoria")) {
    registrar_auditoria("ACTUALIZAR_FOTO_PERSONA", "idpersonal=" . $idPersonal);
}

app_json_response(200, [
    "success" => true,
    "message" => "Foto guardada correctamente.",
    "idpersonal" => $idPersonal,
    "ruta_web" => "/app/_lib/file/img/fotos/" . $nombreArchivo
]);