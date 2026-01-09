<?php
session_start();
include "conexion.php";

if (!isset($_SESSION["usuario"])) {
    header("Location: index.php");
    exit();
}

$mensaje = "";

// --- PROCESAR CSV
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["csv"])) {

    // Eliminar existencias anteriores
    mysqli_query($conexion, "TRUNCATE TABLE existencias");

    $archivo_tmp = $_FILES["csv"]["tmp_name"];
    $nombre = $_FILES["csv"]["name"];
    $destino = "uploads/" . $nombre;

    if (!file_exists("uploads")) mkdir("uploads", 0777, true);

    if (move_uploaded_file($archivo_tmp, $destino)) {

        $mensaje = "✅ Archivo subido correctamente.";

        if (($csv = fopen($destino, "r")) !== false) {

            $linea = 0;
            $fecha_hoy = date("Y-m-d H:i:s"); // Fecha de la carga

            while (($datos = fgetcsv($csv, 1000, ",")) !== false) {

                $linea++;
                if ($linea == 1) continue; // Saltar encabezado

                $item     = mysqli_real_escape_string($conexion, $datos[0] ?? '');
                $producto = mysqli_real_escape_string($conexion, $datos[1] ?? '');
                $cantidad = intval($datos[5] ?? 0);
                $clave    = ""; // por defecto vacío

                if ($producto === "") continue;

                // Si cantidad es 0, NO INSERTA NADA (se elimina automáticamente)
                if ($cantidad <= 0) continue;

                // Insertar registro válido
                $sqlInsert = "INSERT INTO existencias (item, producto, cantidad, clave, fecha_subida) 
                              VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conexion, $sqlInsert);
                mysqli_stmt_bind_param($stmt, "ssiss", $item, $producto, $cantidad, $clave, $fecha_hoy);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            fclose($csv);
            $mensaje .= "<br>📦 Inventario cargado correctamente.";

        } else {
            $mensaje = "⚠️ No se pudo abrir el archivo CSV.";
        }

    } else {
        $mensaje = "❌ Error al subir el archivo.";
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Subir CSV - Inventario</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color: #f8f9fa; }
.sidebar { height: 100vh; background-color: #343a40; color: white; position: fixed; width: 220px; padding-top: 20px; }
.sidebar a, .dropdown-btn { color: #ddd; display: block; padding: 10px 20px; text-decoration: none; background: none; border: none; width: 100%; text-align: left; }
.sidebar a:hover, .dropdown-btn:hover { background-color: #495057; color: white; }
.content { margin-left: 230px; padding: 20px; }
.dropdown-container { display: none; padding-left: 20px; }
</style>
</head>
<body>

<div class="sidebar">
  <h4 class="text-center mb-4">📊 Panel</h4>
  <a href="panel.php">🏠 Inicio</a>

  <button class="dropdown-btn">📦 Inventario ▼</button>
  <div class="dropdown-container" style="display:block;">
    <a href="inventario_subir.php" class="fw-bold text-light">Subir CSV</a>
    <a href="existencia.php">Existencia</a>
    <a href="subircargo.php">Subir Clave de Ventas</a>
    <a href="subirclaves.php">Subir Cargos</a>
    <a href="descargar_excel.php">📥 Descargar inventario en Excel</a>
  </div>

  <a href="#">⚙️ Configuración</a>
  <a href="logout.php" class="text-danger">🚪 Cerrar sesión</a>
</div>

<div class="content">
  <h3>📤 Subir archivo CSV</h3>
  <hr>
  <form method="POST" enctype="multipart/form-data" class="mt-3">
    <div class="mb-3">
      <input type="file" name="csv" accept=".csv" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Cargar archivo</button>
  </form>

  <?php if ($mensaje): ?>
    <div class="alert alert-info mt-3"><?= $mensaje ?></div>
  <?php endif; ?>
</div>

</body>
</html>
