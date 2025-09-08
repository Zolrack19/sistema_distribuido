<?php 
  session_start();
  require __DIR__ . "/config.php";

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = ["ok" => false];
    if (!isset($_FILES['archivo']) || $_FILES['archivo']["tmp_name"] == null) {
      $json["mensajeError"] = "Error al subir el archivo al formulario, intente de nuevo";
      goto ala;
    }
    if (0 >= $_POST["monto"]) {
      $json["mensajeError"] = "El monto debe ser mayor que cero, operación cancelada"; goto ala;
    }

    // verifica al emisor
    $conexion = getConexionDB($_SESSION["usuario"], $_SESSION["clave"]);
    $conexion->begin_transaction();

    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE celular = ?");
    $stmt->bind_param("s", $_POST["emisor"]);
    $stmt->execute();
    $rsp_stmt = $stmt->get_result();
    if ($rsp_stmt->num_rows == 0) {
      $json["mensajeError"] = "Error, emisor no válido"; goto ala;
    }
    $emisor = $rsp_stmt->fetch_assoc();
    $stmt->close();
    if ($emisor["saldo"] < $_POST["monto"]) {
      $json["mensajeError"] = "El monto supera al saldo del emisor, operación cancelada"; goto ala;
    }

    // verifica al receptor
    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE celular = ?");
    $stmt->bind_param("s", $_POST["receptor"]);
    $stmt->execute();
    $rsp_stmt = $stmt->get_result();
    if ($rsp_stmt->num_rows == 0) {
      $json["mensajeError"] = "Error, receptor no válido"; goto ala;
    }
    $receptor = $rsp_stmt->fetch_assoc();
    if ($emisor["id"] == $receptor["id"]) {
      $json["mensajeError"] = "Emisor y receptor no pueden ser la misma persona"; goto ala;
    }
    $stmt->close();

    $archivoTmp = $_FILES['archivo']['tmp_name'];
    $tiposPermitidos = ['application/pdf', 'image/png', 'image/jpeg'];
    // Para el máximo de 5MB, pero se tiene que configurar el php ya que por defecto máximo es de 2MB, sino sale error
    // $maxSize = 5 * 1024 * 1024; // 5MB en bytes
    // if ($_FILES['archivo']['size'] > $maxSize) {
    //   $json["mensajeError"] = "Archivo demasiado grande. El tamaño máximo permitido es de 5MB";
    //   goto ala;
    // }
    if (!in_array(mime_content_type($archivoTmp), $tiposPermitidos)) {
      $conexion->rollback();
      $json["mensajeError"] = "Formato no válido. Solo se aceptan PDF, PNG y JPG"; goto ala;
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
      $json["mensajeError"] = "Ocurrió un error interno en el servidor de archivos, intente más tarde"; goto ala;
    };

    $stmt = $conexion->prepare("INSERT INTO transacciones(emisor, receptor, monto, filesustento) values (?, ?, ?, ?)");
    $stmt->bind_param("ssds", $_POST["emisor"], $_POST["receptor"], $_POST["monto"], $nombre);
    $stmt->execute();
    $stmt->close();

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

    $r_transaccion = $conexion->query(<<<SQL
      SELECT u.nombre as emisor, us.nombre as receptor, t.monto, t.fecha
      FROM transacciones t  
      INNER JOIN usuarios u ON u.celular = t.emisor 
      INNER JOIN usuarios us ON us.celular = t.receptor
      ORDER BY t.id desc
      LIMIT 1
    SQL);


    $conexion->commit();
    $conexion->close();
    $fila = $r_transaccion->fetch_assoc();

    $json["ok"] = true;
    $json["emisor"] = $fila["emisor"];
    $json["receptor"] = $fila["receptor"];
    $json["monto"] = $fila["monto"];
    $json["fecha"] = $fila["fecha"];
    $json["filesustento"] = $nombre;
    $json["saldoEmisor"] = $emisor["saldo"];
    $json["saldoReceptor"] = $receptor["saldo"];
    
    ala:
    echo json_encode($json);
  }
?>