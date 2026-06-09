<?php
require_once "conexion.php";

$nombre = "Cesar";
$correo = "cesar@correo.com";
$passwordPlano = "12345";

$hash = password_hash($passwordPlano, PASSWORD_DEFAULT);

$sql = "INSERT INTO usuarios (nombre, correo, password) VALUES (:nombre, :correo, :password)";
$stmt = $conexion->prepare($sql);
$stmt->execute([
    ":nombre" => $nombre,
    ":correo" => $correo,
    ":password" => $hash
]);

echo "Usuario creado correctamente";
?>