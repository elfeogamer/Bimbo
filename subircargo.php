<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: index.php");
    exit();
}

require "conexion.php";

$mensaje = "";

// --- FUNCION: actualizar existencias sumando si ya existe (item+producto+clave)
function sumar_existencia($conexion, $item, $producto, $cantidad, $clave) {
    $item = trim($item);
    $producto = trim($producto);
    $clave = trim($clave);
    $cantidad = (int)$cantidad;

    if ($producto === "" && $clave === "") return;

    // revisar si existe
    $sql_check = "SELECT id, cantidad FROM existencias WHERE item = ? AND producto = ? AND clave = ?";
    $stmt = mysqli_prepare($conexion, $sql_check);
    mysqli_stmt_bind_param($stmt, "sss", $item, $producto, $clave);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    mysqli_stmt_bind_result($stmt, $id_exist, $cantidad_actual);
    $encontrado = mysqli_stmt_num_rows($stmt) > 0;
    if ($encontrado) {
        mysqli_stmt_fetch($stmt);
        $nueva = (int)$cantidad_actual + $cantidad;
        $sql_upd = "UPDATE existencias SET cantidad = ? WHERE id = ?";
        $stmt2 = mysqli_prepare($conexion, $sql_upd);
        mysqli_stmt_bind_param($stmt2, "ii", $nueva, $id_exist);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
    } else {
        $sql_ins = "INSERT INTO existencias (item, producto, cantidad, clave) VALUES (?, ?, ?, ?)";
        $stmt3 = mysqli_prepare($conexion, $sql_ins);
        mysqli_stmt_bind_param($stmt3, "ssis", $item, $producto, $cantidad, $clave);
        mysqli_stmt_execute($stmt3);
        mysqli_stmt_close($stmt3);
    }
    mysqli_stmt_close($stmt);
}

// --- PROCESAR FORMULARIO MANUAL
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['manual'])) {
    $item = $_POST['item'] ?? '';
    $producto = $_POST['producto'] ?? '';
    $cantidad = intval($_POST['cantidad'] ?? 0);
    $clave = $_POST['clave'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $usuario = $_SESSION["usuario"];

    if ($producto !== "" && $cantidad > 0) {
        // guardar en tabla cargos
        $sql = "INSERT INTO cargos (origen, item, producto, cantidad, clave, tipo, usuario) VALUES ('manual', ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, "ssisss", $item, $producto, $cantidad, $clave, $tipo, $usuario);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // actualizar existencias (sumar)
        sumar_existencia($conexion, $item, $producto, $cantidad, $clave);

        $mensaje = "✅ Cargo manual guardado y existencias actualizadas.";
    } else {
        $mensaje = "⚠️ Completa Producto y Cantidad mayor a 0.";
    }
}

// --- PROCESAR CSV DE CARGOS
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES['csv']) && isset($_POST['csv_submit'])) {

    $tmp = $_FILES['csv']['tmp_name'];
    $nombre = $_FILES['csv']['name'];

    if (($handle = fopen($tmp, "r")) !== FALSE) {

        $fila = 0;
        while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
            $fila++;
            if ($fila == 1) continue; // saltar encabezado

            if (count($data) < 11) continue; // al menos hasta índice 10

            $item = $data[5] ?? '';
            $producto = $data[6] ?? '';
            $cantidad = intval($data[7] ?? 0);
            $clave = $data[10] ?? ''; // ← CORREGIDO: índice 10 para fecha/clave

            if ($producto !== "" && $cantidad > 0) {
                // Guardar en cargos (origen = nombre de archivo)
                $sql = "INSERT INTO cargos (origen, item, producto, cantidad, clave, usuario) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conexion, $sql);
                mysqli_stmt_bind_param($stmt, "ssisss", $nombre, $item, $producto, $cantidad, $clave, $_SESSION["usuario"]);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                // Actualizar existencias (sumar)
                sumar_existencia($conexion, $item, $producto, $cantidad, $clave);
            }
        }
        fclose($handle);

        $mensaje = "✅ Archivo '$nombre' procesado: cargos guardados y existencias actualizadas.";
    } else {
        $mensaje = "❌ Error al leer el archivo CSV.";
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
    <div class="dropdown-container" style="display:block;">
      <a href="inventario_subir.php">Subir CSV</a>
      <a href="existencia.php">Existencia</a>
      <a href="subircargo.php"class="fw-bold text-light">Subir Clave de Ventas</a>
      <a href="subirclaves.php" >Subir Cargo</a>
      <a href="descargar_excel.php">📥 Descargar inventario en Excel</a>
    </div>

    <a href="#">⚙️ Configuración</a>
    <a href="logout.php" class="text-danger">🚪 Cerrar sesión</a>
  </div>

  <div class="content">
    <h3>📥 Subir Clave de Rroducto</h3>
    <p class="text-muted">Puedes subir un CSV con cargos o registrar un cargo manualmente. Al guardar, las cantidades se suman en existencias si coinciden item+producto+clave.</p>

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
            <button type="submit" name="csv_submit" class="btn btn-primary">Procesar CSV</button>
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

            <button class="btn btn-warning">Cargo</button>
          </form>
        </div>
      </div>
    </div>

    <hr class="my-4">
    <h5>Últimos cargos</h5>
    <div class="table-responsive">
      <table class="table table-striped table-bordered">
        <thead class="table-dark">
          <tr><th>Fecha</th><th>Origen</th><th>Item</th><th>Cantidad</th><th>Clave</th><th>Usuario</th></tr>
        </thead>
        <tbody>
        <?php
        $q = "SELECT fecha, origen, item, cantidad, clave, usuario FROM cargos ORDER BY id DESC LIMIT 50";
        $r = mysqli_query($conexion, $q);
        if ($r && mysqli_num_rows($r) > 0) {
            while ($row = mysqli_fetch_assoc($r)) {
                echo "<tr>
                        <td>".htmlspecialchars($row['fecha'])."</td>
                        <td>".htmlspecialchars($row['origen'])."</td>
                        <td>".htmlspecialchars($row['item'])."</td>
                        <td>".htmlspecialchars($row['cantidad'])."</td>
                        <td>".htmlspecialchars($row['clave'])."</td>
                        <td>".htmlspecialchars($row['usuario'])."</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='7'>No hay cargos registrados todavía.</td></tr>";
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
