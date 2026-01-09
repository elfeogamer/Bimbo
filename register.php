<?php
include "conexion.php";
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"]);
    $correo = trim($_POST["correo"]);
    $password = trim($_POST["password"]);

    $verificar = "SELECT * FROM usuarios WHERE correo = '$correo'";
    $resultado = mysqli_query($conexion, $verificar);

    if (mysqli_num_rows($resultado) > 0) {
        $mensaje = "⚠️ Ese correo ya está registrado.";
    } else {
        $sql = "INSERT INTO usuarios (nombre, correo, password) VALUES ('$nombre', '$correo', '$password')";
        if (mysqli_query($conexion, $sql)) {
            $mensaje = "✅ Registro exitoso. Ahora puedes iniciar sesión.";
        } else {
            $mensaje = "❌ Error al registrar usuario.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #007bff, #00bcd4);
            height: 100vh; display: flex; justify-content: center; align-items: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .card-register {
            background-color: #fff; border-radius: 15px;
            box-shadow: 0px 5px 25px rgba(0, 0, 0, 0.2);
            width: 100%; max-width: 400px; padding: 40px 30px;
        }
        .btn-primary { border-radius: 10px; }
        .text-link { text-align: center; margin-top: 15px; }
        .text-link a { color: #007bff; text-decoration: none; }
        .text-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card-register">
        <h3 class="text-center text-primary">Crear cuenta</h3>

        <?php if ($mensaje): ?>
            <div class="alert alert-info text-center"><?= $mensaje ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre completo</label>
                <input type="text" class="form-control" name="nombre" required>
            </div>
            <div class="mb-3">
                <label for="correo" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" name="correo" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Registrar</button>
        </form>

        <div class="text-link">
            <p>¿Ya tienes cuenta? <a href="index.php">Inicia sesión</a></p>
        </div>
    </div>
</body>
</html>
