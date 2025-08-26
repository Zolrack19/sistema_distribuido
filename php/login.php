<?php  //usuario_app pass123
session_start();

$usuario = $_POST['usuario'] ?? '';
$clave   = $_POST['clave'] ?? '';

try {
  $conexion = new mysqli("db", $usuario, $clave, "testdb");

  $_SESSION['usuario'] = $usuario;
  $_SESSION['clave'] = $clave;

  header("Location: menu.php");
  exit;
} catch (PDOException $e) {
  echo "Credenciales inválidas";
}