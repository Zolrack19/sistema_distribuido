<?php
header("Access-Control-Allow-Origin: *"); // permite cualquier origen
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, HEAD");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
$usuario = $_REQUEST['usuario'] ?? '';
$clave = $_REQUEST['clave'] ?? '';

if ($usuario == "carlos" && $clave == "12345") {
  session_start();
  $_SESSION["usuario"] = $usuario;
  $_SESSION["clave"] = $clave;
  header("Location: php/menu.php");
} else {
  echo "<h1>No deberías estar aquí</h1>";
  echo $usuario;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title></title>
</head>
<body>
</body>
</html>
