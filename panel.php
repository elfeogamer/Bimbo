<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: index.php");
    exit();
}

require "conexion.php";

// ====== OBTENER DATOS DEL INVENTARIO ======

$sqlResumen = "SELECT 
                COUNT(*) AS total_items,
                SUM(cantidad) AS total_unidades,
                COUNT(DISTINCT clave) AS claves_distintas
              FROM existencias";
$resResumen = $conexion->query($sqlResumen);
$inventario = $resResumen->fetch_assoc();

$sqlUltimaClave = "SELECT clave FROM existencias ORDER BY id DESC LIMIT 1";
$ultimaClave = $conexion->query($sqlUltimaClave)->fetch_assoc();

$sqlPocos = "SELECT producto, cantidad FROM existencias WHERE cantidad < 5 ORDER BY cantidad ASC LIMIT 5";
$pocos = $conexion->query($sqlPocos);

$sqlAltos = "SELECT producto, cantidad FROM existencias WHERE cantidad > 50 ORDER BY cantidad DESC LIMIT 5";
$altos = $conexion->query($sqlAltos);

$sqlSinClave = "SELECT COUNT(*) AS sin_clave FROM existencias WHERE clave = '' OR clave IS NULL";
$sinClave = $conexion->query($sqlSinClave)->fetch_assoc();

$alerta_csv = false;
if (isset($_SESSION["ultima_fecha"])) {
    $fecha = strtotime($_SESSION["ultima_fecha"]);
    if (time() - $fecha > 7 * 24 * 60 * 60) {
        $alerta_csv = true;
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Panel de Control</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma; }

    .sidebar {
      height: 100vh; background-color: #343a40; color: white;
      position: fixed; width: 220px; padding-top: 20px;
    }

    .sidebar a {
      color: #ddd;
      padding: 10px 20px;
      display: block;
      text-decoration: none;
    }

    .sidebar a:hover {
      background-color: #495057;
      color: white;
    }

    /* 🔥 FIX DEL BOTÓN QUE SE VOLVÍA BLANCO */
    .dropdown-btn {
      color: #ddd !important;
      background-color: transparent !important;
      border: none;
      padding: 10px 20px;
      width: 100%;
      text-align: left;
      display: block;
    }

    .dropdown-btn:hover {
      background-color: #495057 !important;
      color: #fff !important;
    }

    .dropdown-btn:focus,
    .dropdown-btn:active {
      background-color: transparent !important;
      color: #fff !important;
      outline: none !important;
      box-shadow: none !important;
    }
    /* FIN DEL FIX */

    .content { margin-left: 230px; padding: 20px; }
    .dropdown-container { padding-left: 20px; }
    .card { border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <h4 class="text-center mb-4">📊 Panel</h4>
    <a href="panel.php" class="fw-bold text-light">🏠 Inicio</a>

    <button class="dropdown-btn">📦 Inventario ▼</button>
    <div class="dropdown-container" style="display:block;">
      <a href="inventario_subir.php">Subir CSV</a>
      <a href="existencia.php">Existencia</a>
      <a href="subircargo.php">Subir Clave de Ventas</a>
      <a href="subirclaves.php">Subir Cargo</a>
      <a href="descargar_excel.php">📥 Descargar inventario en Excel</a>
    </div>

    <a href="#">⚙️ Configuración</a>
    <a href="logout.php" class="text-danger">🚪 Cerrar sesión</a>
  </div>

  <!-- Contenido -->
  <div class="content">
    <h3>Bienvenido <?= htmlspecialchars($_SESSION["usuario"]) ?> 👋</h3>
    <hr>

    <!-- ALERTAS -->
    <?php if ($alerta_csv): ?>
      <div class="alert alert-warning">
        ⚠️ El inventario no se actualiza desde hace más de 7 días.
      </div>
    <?php endif; ?>

    <?php if ($sinClave["sin_clave"] > 0): ?>
      <div class="alert alert-danger">
        ❗ Hay <?= $sinClave["sin_clave"] ?> productos sin clave asignada.
      </div>
    <?php endif; ?>

    <!-- Resumen -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card bg-primary text-white text-center p-3">
          <h5>Total de items</h5>
          <p class="mb-0"><?= $inventario["total_items"] ?></p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-success text-white text-center p-3">
          <h5>Unidades totales</h5>
          <p class="mb-0"><?= $inventario["total_unidades"] ?></p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-warning text-dark text-center p-3">
          <h5>Claves distintas</h5>
          <p class="mb-0"><?= $inventario["claves_distintas"] ?></p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-dark text-white text-center p-3">
          <h5>Última clave</h5>
          <p class="mb-0"><?= $ultimaClave["clave"] ?? "N/A" ?></p>
        </div>
      </div>
    </div>

    <!-- Productos con poca existencia -->
    <h4 class="mt-4">🔻 Productos con poca existencia</h4>
    <ul>
      <?php while ($p = $pocos->fetch_assoc()): ?>
        <li><?= $p["producto"] ?> — <?= $p["cantidad"] ?></li>
      <?php endwhile; ?>
    </ul>

    <!-- Productos con alta existencia -->
    <h4 class="mt-4">🔺 Productos con mucha existencia</h4>
    <ul>
      <?php while ($a = $altos->fetch_assoc()): ?>
        <li><?= $a["producto"] ?> — <?= $a["cantidad"] ?></li>
      <?php endwhile; ?>
    </ul>

  </div>

  <script>
    const dropdown = document.querySelector('.dropdown-btn');
    const container = document.querySelector('.dropdown-container');

    dropdown.addEventListener('click', () => {
      container.style.display = container.style.display === 'block' ? 'none' : 'block';
    });
  </script>

</body>
</html>
