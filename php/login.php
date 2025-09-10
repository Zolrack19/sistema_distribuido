<?php  //carlos 12345
  require __DIR__ . "/config.php";

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    $json = ["ok" => false];

    $usuario = $_POST['usuario'] ?? '';
    $clave = $_POST['clave'] ?? '';
    if ($usuario != "carlos" || $clave != "12345") {
      $json["mensajeError"] = "Contraseña o usuario no válido"; goto ala;
    }

    $conexion = getConexionDBWrapper($usuario, $clave)[0];
    if ($conexion == null) {
      $json["mensajeError"] = "No se pudo establecer conexión con el sistema, por favor intente más tarde";
      goto ala;
    }

    $_SESSION['usuario'] = $usuario;
    $_SESSION['clave'] = $clave;
    $json["ok"] = true;

    ala:
    echo json_encode($json);
  }
?>