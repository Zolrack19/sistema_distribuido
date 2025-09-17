<?php  //carlos 12345

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = ["ok" => false];

    $usuario = $_POST['usuario'] ?? '';
    $clave = $_POST['clave'] ?? '';
    if ($usuario != "carlos" || $clave != "12345") {
      $json["mensajeError"] = "Contraseña o usuario no válido"; goto ala;
    }
    $json["ok"] = true;
    $json["servidor"] = "http://172.19.0.3";
    $json["usuario"] = $usuario;
    $json["clave"] = $clave;

    ala:
    echo json_encode($json);
  }
?>