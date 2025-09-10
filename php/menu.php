<?php 
  session_start();
  require __DIR__ . "/config.php";
  
  if (!isset($_SESSION['usuario']) || !isset($_SESSION['clave'])) {
    header("Location: /index.php");
    exit;
  }

  $conexion = getConexionDBWrapper($_SESSION["usuario"], $_SESSION["clave"])[0];
  $r_usuarios = $conexion->query("SELECT * FROM usuarios;");
  $r_transacciones = $conexion->query(<<<SQL
    SELECT u.nombre as emisor, us.nombre as receptor, t.monto, t.fecha, t.filesustento
    FROM transacciones t  
    INNER JOIN usuarios u ON u.celular = t.emisor 
    INNER JOIN usuarios us ON us.celular = t.receptor
    ORDER BY t.id desc
  SQL);
  $conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Menú Principal</title>
  <link rel="stylesheet" href="/css/menu.css">
  <script type="module" src="/js/menu.js"></script>
</head>
<body>

  <div class="container-inicial">
    <div class="form-container">
      <form id="form-transaccion" class="form-principal" method="post" enctype="multipart/form-data">
        <div class="form-group">
          <label for="emisor">Emisor</label>
          <input type="number" id="emisor" name="emisor" required>
        </div>
  
        <div class="form-group">
          <label for="receptor">Receptor</label>
          <input type="number" id="receptor" name="receptor" required>
        </div>
  
        <div class="form-group">
          <label for="monto">Monto</label>
          <input type="number" id="monto" name="monto" step="0.01" required>
        </div>
  
        <div class="form-group">
          <label for="archivo">Adjuntar Archivo</label>
          <input type="file" id="archivo" name="archivo" accept=".pdf,.png,.jpg,.jpeg" required>
        </div>
  
        <button type="submit" class="btn-submit">Enviar</button>
      </form>
    </div>

    <table>
      <thead>
        <tr>
          <th>Celular</th>
          <th>Nombre</th>
          <th>Saldo</th>
        </tr>
      </thead>
      <tbody id="tbody-usuarios">
        <?php 
          while ($fila = $r_usuarios->fetch_assoc()) {
            echo 
            "<tr data-id=" . $fila["celular"] .">" .
              "<td data-col_name='celular'>" . $fila["celular"] . "</td>" .
              "<td data-col_name='nombre'>" . $fila["nombre"] . "</td>" .
              "<td data-col_name='saldo'>" . $fila["saldo"] . "</td>" .
            "</tr>";
          }
        ?>
      </tbody>
    </table>
  </div>

  <h2>Transacciones</h2>
  <table>
    <thead>
      <tr>
        <th>Emisor</th>
        <th>Receptor</th>
        <th>Monto</th>
        <th>Fecha</th>
        <th>Link operación</th>
      </tr>
    </thead>
    <tbody id="tbody-transacciones">
      <?php 
        while ($fila = $r_transacciones->fetch_assoc()) {
          echo 
          "<tr>
            <td>$fila[emisor]</td>
            <td>$fila[receptor]</td>
            <td>$fila[monto]</td>
            <td>$fila[fecha]</td>
            <td><span class='link-operacion'>$fila[filesustento]</span></td>
          </tr>";
        }
      ?>
    </tbody>
  </table>

</body>
</html>