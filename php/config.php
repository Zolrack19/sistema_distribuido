<?php
  mysqli_report(MYSQLI_REPORT_OFF);
  define("PROJECT_ROOT", realpath(__DIR__ . "/.."));
  define("DB_IP", "db");
  define("DB_NAME", "SISTEMAPAGOS");
  define("STORAGE_IP", "storage");
  define("DB_RESPALDO_IP", "db_respaldo");

  function getConexionDBWrapper($user, $clave) {
    $archivo = fopen(PROJECT_ROOT . "/tmp.txt", "r");
    $valor = fgets($archivo);
    fclose($archivo);

    $conexion = @new mysqli(DB_IP, $user, $clave, DB_NAME);
    $db_principal = true;
    if ($conexion->connect_errno) {
      if ($valor == "1") { // falla db principal
        $archivo = fopen(PROJECT_ROOT . "/tmp.txt", "w");
        fwrite($archivo, "0");
        fclose($archivo);
      }
      $conexion = @new mysqli(DB_RESPALDO_IP, $user, $clave, DB_NAME);
      $db_principal = false;
    } else if ($valor == "0") { // db se recuperó, necesita sincronizar datos
      $conexion_respaldo = @new mysqli(DB_RESPALDO_IP, $user, $clave, DB_NAME);
      $conexion_respaldo->begin_transaction();
      $conexion->begin_transaction();
      
      $error = false;
      $query = "INSERT INTO transacciones(emisor, receptor, monto, fecha, filesustento) VALUES ";
      $set = [];

      $r_respaldo = $conexion_respaldo->query("SELECT * FROM transacciones ORDER BY id DESC");
      if ($r_respaldo->num_rows == 0) goto __cerrar;
      while ($fila = $r_respaldo->fetch_assoc()) {
        $query .= "\n('$fila[emisor]', '$fila[receptor]', $fila[monto], '$fila[fecha]', '$fila[filesustento]'),";
        if (!isset($set["$fila[emisor]"])) {
          $set["$fila[emisor]"] = "'$fila[emisor]'";
        }
        if (!isset($set["$fila[receptor]"])) {
          $set["$fila[receptor]"] = "'$fila[receptor]'";
        }
      }

      // insertar las transacciones a la db general
      $query = substr($query, 0, -1); // para eliminar la última coma
      $query .= ";";
      if (!$conexion->query($query)) {
        $error = true;
        $conexion->rollback(); goto __cerrar;
      }

      $query = "SELECT id, saldo FROM usuarios WHERE celular IN (" . implode(", ", array_values($set)) . ");";
      $r_respaldo = $conexion_respaldo->query($query);
      $query = "UPDATE usuarios SET saldo = CASE id ";
      $str_ids = "";
      while ($fila = $r_respaldo->fetch_assoc()) {
        $query .= "\n WHEN $fila[id] THEN $fila[saldo]";
        $str_ids .= "$fila[id],";
      }

      // actualizar el saldo de usuarios que realizaron transacciones en la db de respaldo
      $str_ids = substr($str_ids, 0, -1);
      $query .= "\nEND\nWHERE id IN ($str_ids);";
      if (!$conexion->query($query)) {
        $error = true;
        $conexion->rollback(); goto __cerrar;
      }

      // eliminar transacciones de la db de respaldo
      if (!$conexion_respaldo->query("DELETE FROM transacciones")) {
        $error = true;
        $conexion_respaldo->rollback(); goto __cerrar;
      }

      $conexion_respaldo->commit();
      $conexion->commit();

      $archivo = fopen(PROJECT_ROOT . "/tmp.txt", "w");
      fwrite($archivo, "1");
      fclose($archivo);
      
      __cerrar:
      $conexion_respaldo->close();
      if ($error) { // se niega el acceso, algo salió mal en la sincronización
        $conexion->close();
        $conexion = null;
      }
    }
    return (!$conexion->connect_errno) ? [$conexion, $db_principal] : null;
  }
?>