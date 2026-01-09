<?php
session_start();
include "conexion.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = trim($_POST["correo"]);
    $password = trim($_POST["password"]);

    $sql = "SELECT * FROM usuarios WHERE correo = '$correo'";
    $resultado = mysqli_query($conexion, $sql);

    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $usuario = mysqli_fetch_assoc($resultado);

        if ($usuario["password"] === $password) {
            $_SESSION["usuario"] = $usuario["correo"];
            header("Location: panel.php");
            exit();
        } else {
            $error = "❌ Contraseña incorrecta.";
        }
    } else {
        $error = "⚠️ No existe una cuenta con ese correo.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #007bff, #00bcd4);
            height: 100vh; display: flex; justify-content: center; align-items: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .card-login {
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
    <div class="card-login">
        <h3 class="text-center text-primary">Iniciar sesión</h3>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger text-center"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="correo" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="correo" name="correo" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Entrar</button>
        </form>

        <div class="text-link">
            <p>¿No tienes cuenta? <a href="register.php">Crear una nueva</a></p>
        </div>
    </div>
</body>
</html>
