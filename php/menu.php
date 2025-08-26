<?php 
  session_start();
  
  if (!isset($_SESSION['usuario']) || !isset($_SESSION['clave'])) {
    header("Location: /index.php");
    exit;
  }
  $conexion = new mysqli("db", $_SESSION['usuario'], $_SESSION['clave'], "testdb");
  $r_usuarios = $conexion->query("SELECT * FROM usuarios;");
  $r_transacciones = $conexion->query(<<<SQL
    SELECT u.nombre as emisor, us.nombre as receptor, t.monto, t.fecha
    FROM transacciones t  
    INNER JOIN usuarios u ON u.celular = t.emisor 
    INNER JOIN usuarios us ON us.celular = t.receptor
  SQL);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Menú Principal</title>
  <link rel="stylesheet" href="/css/menu.css">
</head>
<body>
  <h2>Usuarios</h2>
  <table>
    <thead>
      <tr>
        <th>Celular</th>
        <th>Nombre</th>
        <th>Saldo</th>
      </tr>
    </thead>
    <tbody>
      <?php 
        while ($fila = $r_usuarios->fetch_assoc()) {
          echo 
          "<tr>" .
            "<td>" . $fila["celular"] . "</td>" .
            "<td>" . $fila["nombre"] . "</td>" .
            "<td>" . $fila["saldo"] . "</td>" .
          "</tr>";
        }
      ?>
    </tbody>
  </table>

  <h2>Transacciones</h2>
  <table>
    <thead>
      <tr>
        <th>Emisor</th>
        <th>Receptor</th>
        <th>Monto</th>
        <th>Fecha</th>
      </tr>
    </thead>
    <tbody>
      <?php 
        while ($fila = $r_transacciones->fetch_assoc()) {
          echo 
          "<tr>" .
            "<td>" . $fila["emisor"] . "</td>" .
            "<td>" . $fila["receptor"] . "</td>" .
            "<td>" . $fila["monto"] . "</td>" .
            "<td>" . $fila["fecha"] . "</td>" .
          "</tr>";
        }
      ?>
    </tbody>
  </table>

</body>
</html>