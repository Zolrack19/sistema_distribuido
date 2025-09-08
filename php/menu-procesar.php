<?php 
  session_start();
  require __DIR__ . "/config.php";

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = ["ok" => false];
    if (!isset($_FILES['archivo']) || $_FILES['archivo']["tmp_name"] == null) {
      $json["mensajeError"] = "Error al subir el archivo al formulario, intente de nuevo";
      goto ala;
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
      $json["mensajeError"] = "Formato no válido. Solo se aceptan PDF, PNG y JPG";
      goto ala;
    }
    
    $nombre = "operacion_" . bin2hex(random_bytes(16));

    // url o ip del servidor storage
    $url = "http://" . STORAGE_IP . "/upload.php";

    // petición POST con CURL
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
      $json["mensajeError"] = "Ocurrió un error interno en el servidor de archivos, intente más tarde";
      goto ala;
    };

    $conexion = getConexionDB($_SESSION["usuario"], $_SESSION["clave"]);
    $stmt = $conexion->prepare("INSERT INTO transacciones(emisor, receptor, monto, filesustento) values (?, ?, ?, ?)");
    $stmt->bind_param("ssds", $_POST["emisor"], $_POST["receptor"], $_POST["monto"], $nombre);
    $stmt->execute();
    $stmt->close();

    $r_transaccion = $conexion->query(<<<SQL
      SELECT u.nombre as emisor, us.nombre as receptor, t.monto, t.fecha
      FROM transacciones t  
      INNER JOIN usuarios u ON u.celular = t.emisor 
      INNER JOIN usuarios us ON us.celular = t.receptor
      ORDER BY t.id desc
      LIMIT 1
    SQL);
    $conexion->close();

    $fila = $r_transaccion->fetch_assoc();

    $json["ok"] = true;
    $json["emisor"] = $fila["emisor"];
    $json["receptor"] = $fila["receptor"];
    $json["monto"] = $fila["monto"];
    $json["fecha"] = $fila["fecha"];
    $json["filesustento"] = $nombre;
    
    ala:
    echo json_encode($json);
  }
?>