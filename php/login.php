<?php  //carlos 12345
  $mensaje = "";

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    $json = ["ok" => false];
    mysqli_report(MYSQLI_REPORT_OFF);

    // cambiar atributos por los reales
    $usuario = $_POST['usuario'] ?? '';
    $clave = $_POST['clave'] ?? '';
    $db_ip = "db";
    $db_name = "SISTEMAPAGOS";
    $storage_ip = "storage";
    $db_respaldo_ip = "db_respaldo";

    $db_principal = true;

    $conexion = @new mysqli($db_ip, $usuario, $clave, $db_name);

    if (!$conexion->connect_errno) goto ala;
    else if (!(@new mysqli($db_respaldo_ip, $usuario, $clave, $db_name))->connect_errno) {
      $db_principal = false;
      goto ala;
    }
    $json["mensajeError"] = "No se pudo establecer conexión con el sistema, por favor intente más tarde";
    echo json_encode($json);
    return;

    ala:
    $_SESSION['usuario'] = $usuario;
    $_SESSION['clave'] = $clave;
    $_SESSION['db_ip'] = ($db_principal) ? $db_ip : $db_respaldo_ip;
    $_SESSION['db_name'] = $db_name;
    $_SESSION['storage_ip'] = $storage_ip;
    $_SESSION['db_respaldo_ip'] = $db_respaldo_ip;
    $json["ok"] = true;
    echo json_encode($json);
  }
?>