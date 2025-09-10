<?php 
  session_start();
  require __DIR__ . "/config.php";

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = ["ok" => false];
    if (!isset($_FILES['archivo']) || $_FILES['archivo']["tmp_name"] == null) {
      $json["mensajeError"] = "Error al subir el archivo al formulario, intente de nuevo"; goto ala;
    }
    $archivoTmp = $_FILES['archivo']['tmp_name'];
    $tiposPermitidos = ['application/pdf', 'image/png', 'image/jpeg'];
    // Para el máximo de 5MB, pero se tiene que configurar el php ya que por defecto máximo es de 2MB, sino sale error
    // $maxSize = 5 * 1024 * 1024; // 5MB en bytes
    // if ($_FILES['archivo']['size'] > $maxSize) {
    //   $json["mensajeError"] = "Archivo demasiado grande. El tamaño máximo permitido es de 5MB";
    //   goto ala;
    // }
    if (!in_array(mime_content_type($archivoTmp), $tiposPermitidos)) {
      $json["mensajeError"] = "Formato no válido. Solo se aceptan PDF, PNG y JPG"; goto ala;
    }
    if (0 >= $_POST["monto"]) {
      $json["mensajeError"] = "El monto debe ser mayor que cero, operación cancelada"; goto ala;
    }

    // verifica al emisor
    $conexionWrapper = getConexionDBWrapper($_SESSION["usuario"], $_SESSION["clave"]);
    $conexion = $conexionWrapper[0];
    $conexion_principal = $conexionWrapper[1]; 

    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE celular = ?");
    $stmt->bind_param("s", $_POST["emisor"]);
    $stmt->execute();
    $rsp_stmt = $stmt->get_result();
    if ($rsp_stmt->num_rows == 0) {
      $json["mensajeError"] = "Error, emisor no válido"; goto cerrar_conexion;
    }
    $emisor = $rsp_stmt->fetch_assoc();
    if ($emisor["saldo"] < $_POST["monto"]) {
      $json["mensajeError"] = "El monto supera al saldo del emisor, operación cancelada"; goto cerrar_conexion;
    }
    $stmt->close();

    // verifica al receptor
    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE celular = ?");
    $stmt->bind_param("s", $_POST["receptor"]);
    $stmt->execute();
    $rsp_stmt = $stmt->get_result();
    if ($rsp_stmt->num_rows == 0) {
      $json["mensajeError"] = "Error, receptor no válido"; goto cerrar_conexion;
    }
    $receptor = $rsp_stmt->fetch_assoc();
    if ($emisor["id"] == $receptor["id"]) {
      $json["mensajeError"] = "Emisor y receptor no pueden ser la misma persona"; goto cerrar_conexion;
    }
    
    $nombre = "operacion_" . bin2hex(random_bytes(16));

    $url = "http://" . STORAGE_IP . "/upload.php";

    $curlSesion = curl_init();
    curl_setopt($curlSesion, CURLOPT_URL, $url);
    curl_setopt($curlSesion, CURLOPT_POST, true);
    curl_setopt($curlSesion, CURLOPT_POSTFIELDS, [
      'archivo' => new CURLFile($archivoTmp, $_FILES['archivo']['type'], $nombre)
    ]);
    curl_setopt($curlSesion, CURLOPT_RETURNTRANSFER, true);

    $rsp_valida = curl_exec($curlSesion);
    curl_close($curlSesion);

    if (!$rsp_valida) {
      $conexion->rollback();
      $json["mensajeError"] = "Ocurrió un error interno en el servidor de archivos, intente más tarde"; goto cerrar_conexion;
    };

    $stmt->close();
    $stmt = $conexion->prepare("INSERT INTO transacciones(emisor, receptor, monto, filesustento) values (?, ?, ?, ?)");
    $stmt->bind_param("ssds", $_POST["emisor"], $_POST["receptor"], $_POST["monto"], $nombre);
    $stmt->execute();

    // actualizar usuarios
    $emisor["saldo"] -= $_POST["monto"];
    $receptor["saldo"] += $_POST["monto"];
    $conexion->query(<<<SQL
      UPDATE usuarios
      SET saldo = CASE id
        WHEN $emisor[id] THEN $emisor[saldo]
        WHEN $receptor[id] THEN $receptor[saldo]
      END
      WHERE id IN ($emisor[id], $receptor[id]);
    SQL);
    if ($conexion_principal) {
      $cnx_temp = @new mysqli(DB_RESPALDO_IP, $_SESSION["usuario"], $_SESSION["clave"], DB_NAME);
      $cnx_temp->query(<<<SQL
        UPDATE usuarios
        SET saldo = CASE id
          WHEN $emisor[id] THEN $emisor[saldo]
          WHEN $receptor[id] THEN $receptor[saldo]
        END
        WHERE id IN ($emisor[id], $receptor[id]);
      SQL);
      $cnx_temp->close();
    }

    $r_transaccion = $conexion->query(<<<SQL
      SELECT u.nombre as emisor, us.nombre as receptor, t.monto, t.fecha
      FROM transacciones t  
      INNER JOIN usuarios u ON u.celular = t.emisor 
      INNER JOIN usuarios us ON us.celular = t.receptor
      ORDER BY t.id desc
      LIMIT 1
    SQL);


    $conexion->commit();
    $fila = $r_transaccion->fetch_assoc();

    $json["ok"] = true;
    $json["emisor"] = $fila["emisor"];
    $json["receptor"] = $fila["receptor"];
    $json["monto"] = $fila["monto"];
    $json["fecha"] = $fila["fecha"];
    $json["filesustento"] = $nombre;
    $json["saldoEmisor"] = $emisor["saldo"];
    $json["saldoReceptor"] = $receptor["saldo"];
    

    cerrar_conexion:
    $conexion->close();
    $stmt->close();

    ala:
    echo json_encode($json);
  }
?>