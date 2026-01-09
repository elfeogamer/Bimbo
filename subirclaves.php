<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: index.php");
    exit();
}

require "conexion.php"; // Conecta con $conexion

$mensaje = "";

/* ─────────────────────────────────────────────
   FUNCION PARA RESTAR EXISTENCIAS Y BORRAR EN 0
   ───────────────────────────────────────────── */
function restar_existencia($conexion, $origen, $item, $producto, $cantidad, $clave, $usuario) {

    // Buscar coincidencia exacta
    $sqlBuscar = "SELECT id, cantidad FROM existencias 
                  WHERE item = ? AND producto = ? AND clave = ?
                  LIMIT 1";
    $stmt = mysqli_prepare($conexion, $sqlBuscar);
    mysqli_stmt_bind_param($stmt, "sss", $item, $producto, $clave);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($res)) {

        $idExist = $row["id"];
        $actual = intval($row["cantidad"]);
        $nueva = $actual - $cantidad;

        if ($nueva <= 0) {
            // eliminar
            $sqlDel = "DELETE FROM existencias WHERE id = ?";
            $stmt2 = mysqli_prepare($conexion, $sqlDel);
            mysqli_stmt_bind_param($stmt2, "i", $idExist);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);
        } else {
            // actualizar
            $sqlUpd = "UPDATE existencias SET cantidad = ? WHERE id = ?";
            $stmt3 = mysqli_prepare($conexion, $sqlUpd);
            mysqli_stmt_bind_param($stmt3, "ii", $nueva, $idExist);
            mysqli_stmt_execute($stmt3);
            mysqli_stmt_close($stmt3);
        }

        // registrar el cargo restado
        $sqlCargo = "INSERT INTO cargos (origen, item, producto, cantidad, clave, tipo, usuario)
                     VALUES (?, ?, ?, ?, ?, 'resta', ?)";
        $stmt4 = mysqli_prepare($conexion, $sqlCargo);
        mysqli_stmt_bind_param($stmt4, "ssisss", $origen, $item, $producto, $cantidad, $clave, $usuario);
        mysqli_stmt_execute($stmt4);
        mysqli_stmt_close($stmt4);
    }
}

/* ─────────────────────────────────────────────
   PROCESAR CSV (RESTAR)
   ───────────────────────────────────────────── */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["csv"])) {

    $archivo_tmp = $_FILES["csv"]["tmp_name"];
    $nombre = $_FILES["csv"]["name"];

    if (($handle = fopen($archivo_tmp, "r")) !== FALSE) {

        $fila = 0;

        while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
            $fila++;

            if ($fila == 1) continue; // Saltar encabezado

            $item     = trim($data[5] ?? "");
            $producto = trim($data[6] ?? "");
            $cantidad = intval($data[7] ?? 0);
            $clave    = trim($data[10] ?? "");

            if ($item === "" || $producto === "" || $cantidad <= 0) continue;

            restar_existencia($conexion, $nombre, $item, $producto, $cantidad, $clave, $_SESSION["usuario"]);
        }

        fclose($handle);

        $mensaje = "✅ Archivo '$nombre' procesado: cantidades restadas y registros guardados.";
    } else {
        $mensaje = "❌ Error al leer el archivo.";
    }
}

/* ─────────────────────────────────────────────
   PROCESAR FORMULARIO MANUAL (RESTAR)
   ───────────────────────────────────────────── */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["manual"])) {

    $item     = trim($_POST["item"]);
    $producto = trim($_POST["producto"]);
    $cantidad = intval($_POST["cantidad"]);
    $clave    = trim($_POST["clave"]);

    if ($producto !== "" && $cantidad > 0) {
        restar_existencia($conexion, "manual", $item, $producto, $cantidad, $clave, $_SESSION["usuario"]);
        $mensaje = "✅ Cargo manual restado correctamente.";
    } else {
        $mensaje = "⚠️ Completa Producto y Cantidad.";
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Subir Cargo - Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .sidebar {
      height: 100vh; background-color: #343a40; color: white;
      position: fixed; top: 0; left: 0; width: 220px; padding-top: 20px;
    }
    .sidebar a, .dropdown-btn {
      color: #ddd; display: block; padding: 10px 20px; text-decoration: none;
      background: none; border: none; width: 100%; text-align: left;
    }
    .sidebar a:hover, .dropdown-btn:hover { background-color: #495057; color: white; }
    .content { margin-left: 230px; padding: 20px; }
    .dropdown-container { display: block; padding-left: 20px; }
    .card { border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
  </style>
</head>
<body>

<div class="sidebar">
    <h4 class="text-center mb-4">📊 Panel</h4>
    <a href="panel.php">🏠 Inicio</a>

    <button class="dropdown-btn">📦 Inventario ▼</button>
    <div class="dropdown-container">
      <a href="inventario_subir.php">Subir CSV</a>
      <a href="existencia.php">Existencia</a>
      <a href="subircargo.php">Subir Clave de Ventas</a>
      <a href="subirclaves.php" class="fw-bold text-light">Subir Cargo</a>
      <a href="descargar_excel.php">📥 Descargar inventario en Excel</a>
    </div>

    <a href="#">⚙️ Configuración</a>
    <a href="logout.php" class="text-danger">🚪 Cerrar sesión</a>
</div>

<div class="content">
    <h3>📤 Subir archivo Cargo</h3>
    <p class="text-muted">Al cargar, las cantidades se RESTAN y se eliminan productos cuando llegan a 0.</p>

    <?php if ($mensaje): ?>
      <div class="alert alert-info"><?= $mensaje ?></div>
    <?php endif; ?>

    <div class="row g-3">

      <!-- SUBIR CSV -->
      <div class="col-md-6">
        <div class="card p-3">
          <h5>Subir CSV de Cargos</h5>
          <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
              <input type="file" name="csv" accept=".csv" class="form-control" required>
            </div>
            <button class="btn btn-danger">Procesar CSV (Restar)</button>
          </form>
        </div>
      </div>

      <!-- FORMULARIO MANUAL -->
      <div class="col-md-6">
        <div class="card p-3">
          <h5>Restar cargo manual</h5>
          <form method="POST">
            <input type="hidden" name="manual" value="1">

            <div class="mb-2">
              <label class="form-label">Item</label>
              <input type="text" name="item" class="form-control">
            </div>

            <div class="mb-2">
              <label class="form-label">Producto</label>
              <input type="text" name="producto" class="form-control" required>
            </div>

            <div class="mb-2">
              <label class="form-label">Cantidad</label>
              <input type="number" name="cantidad" class="form-control" min="1" required>
            </div>

            <div class="mb-2">
              <label class="form-label">Clave</label>
              <input type="text" name="clave" class="form-control">
            </div>

            <button class="btn btn-warning">Restar Cargo</button>
          </form>
        </div>
      </div>

    </div>

    <hr class="my-4">
    <h5>Últimos cargos</h5>

    <div class="table-responsive">
      <table class="table table-striped table-bordered">
        <thead class="table-dark">
          <tr>
            <th>Fecha</th><th>Origen</th><th>Item</th>
            <th>Cantidad</th><th>Clave</th><th>Tipo</th><th>Usuario</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $q = "SELECT fecha, origen, item, cantidad, clave, tipo, usuario 
              FROM cargos 
              ORDER BY id DESC LIMIT 50";
        $r = mysqli_query($conexion, $q);

        if ($r && mysqli_num_rows($r) > 0) {
            while ($row = mysqli_fetch_assoc($r)) {
                echo "<tr>
                        <td>{$row['fecha']}</td>
                        <td>{$row['origen']}</td>
                        <td>{$row['item']}</td>
                        <td>{$row['cantidad']}</td>
                        <td>{$row['clave']}</td>
                        <td>{$row['tipo']}</td>
                        <td>{$row['usuario']}</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='8'>No hay cargos registrados.</td></tr>";
        }
        ?>
        </tbody>
      </table>
    </div>

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
