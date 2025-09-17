<?php
  mysqli_report(MYSQLI_REPORT_OFF);
  define("PROJECT_ROOT", realpath(__DIR__ . "/.."));
  define("DB_IP", "db");
  define("DB_NAME", "SISTEMAPAGOS");
  define("STORAGE_IP", "storage");
  // define("DB_RESPALDO_IP", "db_respaldo");

  function getConexionDBWrapper($user, $clave) {
    $conexion = @new mysqli(DB_IP, $user, $clave, DB_NAME);
    $db_principal = true;
    return (!$conexion->connect_errno) ? [$conexion, $db_principal] : null;
  }
?>