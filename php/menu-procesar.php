<?php 
  session_start();
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $archivoTmp = $_FILES['archivo']['tmp_name'];
    $nombre = "operacion_" . bin2hex(random_bytes(16));

    // url o ip del servidor storage
    $url = "http://storage/upload.php";

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

    // echo "Respuesta del servidor de almacenamiento: <br>";
    // echo $rsp_valida;

    if (!$rsp_valida) exit; 

    $conexion = new mysqli("db", $_SESSION['usuario'], $_SESSION['clave'], "testdb");
    $stmt = $conexion->prepare("INSERT INTO transacciones(emisor, receptor, monto, filesustento) values (?, ?, ?, ?)");
    $stmt->bind_param("ssds", $_POST["emisor"], $_POST["receptor"], $_POST["monto"], $nombre);
    $stmt->execute();

    $r_transaccion = $conexion->query(<<<SQL
      SELECT u.nombre as emisor, us.nombre as receptor, t.monto, t.fecha
      FROM transacciones t  
      INNER JOIN usuarios u ON u.celular = t.emisor 
      INNER JOIN usuarios us ON us.celular = t.receptor
      ORDER BY t.id desc
      LIMIT 1
    SQL);
    $conexion->close();

    // este pedazo para la descarga
    // $contenido = file_get_contents("http://storage/uploads/alchimista.PDF");
    // if ($contenido !== false) {
    // header("Content-Type: application/octet-stream");
    // header("Content-Disposition: attachment; filename=\"" . basename("http://storage/uploads/alchimista.PDF") . "\"");
    // echo $contenido;
    // } else {
    //   echo "Error al descargar el archivo";
    // }
    $fila = $r_transaccion->fetch_assoc();
    $respuesta = [];
    $respuesta["emisor"] = $fila["emisor"];
    $respuesta["receptor"] = $fila["receptor"];
    $respuesta["monto"] = $fila["monto"];
    $respuesta["fecha"] = $fila["fecha"];
    $respuesta["filesustento"] = $nombre;
    echo json_encode($respuesta);
  }
?>