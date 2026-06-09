<?php
header("Content-Type: application/json; charset=UTF-8");
session_start();

if (!isset($_SESSION["idPersonal"])) {
    echo json_encode([
        "success" => false,
        "message" => "Sesión no válida o sin idPersonal."
    ]);
    exit;
}

$idPersonal = (int)$_SESSION["idPersonal"];

if (!isset($_FILES["documento"])) {
    echo json_encode([
        "success" => false,
        "message" => "No se recibió ningún documento."
    ]);
    exit;
}

$archivo = $_FILES["documento"];

if ($archivo["error"] !== UPLOAD_ERR_OK) {
    echo json_encode([
        "success" => false,
        "message" => "Error al subir el archivo.",
        "error" => $archivo["error"]
    ]);
    exit;
}

$nombreOriginal = $archivo["name"];
$tmp = $archivo["tmp_name"];
$tamano = (int)$archivo["size"];

$maxSize = 10 * 1024 * 1024; // 10 MB

if ($tamano <= 0 || $tamano > $maxSize) {
    echo json_encode([
        "success" => false,
        "message" => "El archivo excede el tamaño permitido de 10 MB."
    ]);
    exit;
}

$extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

$extensionesPermitidas = [
    "pdf",
    "jpg",
    "jpeg",
    "png",
    "doc",
    "docx"
];

if (!in_array($extension, $extensionesPermitidas, true)) {
    echo json_encode([
        "success" => false,
        "message" => "Tipo de archivo no permitido."
    ]);
    exit;
}

$carpetaDestino = $_SERVER["DOCUMENT_ROOT"] . "/mi_app/documentos/";

if (!is_dir($carpetaDestino)) {
    if (!mkdir($carpetaDestino, 0777, true)) {
        echo json_encode([
            "success" => false,
            "message" => "No se pudo crear la carpeta destino."
        ]);
        exit;
    }
}

$nombreArchivo = $idPersonal . "_" . date("Ymd_His") . "." . $extension;
$rutaDestino = $carpetaDestino . $nombreArchivo;

if (!move_uploaded_file($tmp, $rutaDestino)) {
    echo json_encode([
        "success" => false,
        "message" => "No se pudo guardar el documento."
    ]);
    exit;
}

chmod($rutaDestino, 0644);

$url = "https://" . $_SERVER["HTTP_HOST"] . "/mi_app/documentos/" . $nombreArchivo;

echo json_encode([
    "success" => true,
    "message" => "Documento guardado correctamente.",
    "archivo" => $nombreArchivo,
    "ruta" => "/mi_app/documentos/" . $nombreArchivo,
    "url" => $url
]);
exit;