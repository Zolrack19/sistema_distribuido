<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title></title>
  <link rel="stylesheet" href="/css/index.css">
</head>
<body>
  <div class="login-container">
    <h2>Iniciar Sesión</h2>
    <form action="/php/login.php" method="post">
      <label for="usuario">Usuario</label>
      <input type="text" id="usuario" name="usuario" placeholder="Ingresa tu usuario" required>
      
      <label for="clave">Contraseña</label>
      <input type="password" id="clave" name="clave" placeholder="Ingresa tu contraseña" required>
      
      <button type="submit">Entrar</button>
    </form>
  </div>
</body>
</html>