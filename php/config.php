<?php
  mysqli_report(MYSQLI_REPORT_OFF);
  define("PROJECT_ROOT", realpath(__DIR__ . "/.."));
  define("DB_IP", "db");
  define("DB_NAME", "SISTEMAPAGOS");
  define("STORAGE_IP", "storage");
  define("DB_RESPALDO_IP", "db_respaldo");

  function getConexionDB($user, $clave) {
    $archivo = fopen(PROJECT_ROOT . "/tmp.txt", "r");
    $valor = fgets($archivo);
    fclose($archivo);

    $conexion = @new mysqli(DB_IP, $user, $clave, DB_NAME);
    if ($conexion->connect_errno) {
      if ($valor == "1") { // falla db principal
        $archivo = fopen(PROJECT_ROOT . "/tmp.txt", "w");
        fwrite($archivo, "0");
        fclose($archivo);
      }
      $conexion = @new mysqli(DB_RESPALDO_IP, $user, $clave, DB_NAME);
    } else if ($valor == "0") { // db se recuperó, necesita sincronizar datos
      $archivo = fopen(PROJECT_ROOT . "/tmp.txt", "w");
      fwrite($archivo, "1");
      fclose($archivo);
    }

    return (!$conexion->connect_errno) ? $conexion : null;
  }
?>