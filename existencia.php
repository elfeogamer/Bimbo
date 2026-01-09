<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["usuario"])) {
    header("Location: index.php");
    exit();
}

require "conexion.php"; 

$mensaje = "";
$tabla_datos = "";

/* -------------------------------------------------
   DIVIDIR PRODUCTO (desde modal)
   ------------------------------------------------- */
if (isset($_POST["dividir_confirmado"])) {

    $item = $_POST["item_sel"];
    $producto = $_POST["producto_sel"];
    $claveOriginal = $_POST["clave_sel"];
    $cantidadActual = intval($_POST["cantidad_sel"]);
    
    $claveNueva = trim($_POST["clave_nueva"]);
    $cantidadSeparada = intval($_POST["cantidad_clave"]);

    if ($claveNueva == "" || $cantidadSeparada <= 0) {
        $mensaje = "❌ Debes llenar todos los campos correctamente.";
    } else if ($cantidadSeparada > $cantidadActual) {
        $mensaje = "❌ No puedes separar más cantidad de la que existe.";
    } else {

        // Restar cantidad
        $nuevaCantidadOriginal = $cantidadActual - $cantidadSeparada;

        $sqlUpdate = "UPDATE existencias 
                      SET cantidad = ?
                      WHERE item = ? AND producto = ? AND clave = ?";

        $stmtU = mysqli_prepare($conexion, $sqlUpdate);
        mysqli_stmt_bind_param($stmtU, "isss",
            $nuevaCantidadOriginal, $item, $producto, $claveOriginal);
        mysqli_stmt_execute($stmtU);

        // Si queda en 0, borrar
        if ($nuevaCantidadOriginal == 0) {
            $sqlDelete = "DELETE FROM existencias 
                          WHERE item = ? AND producto = ? AND clave = ?";
            $stmtD = mysqli_prepare($conexion, $sqlDelete);
            mysqli_stmt_bind_param($stmtD, "sss", $item, $producto, $claveOriginal);
            mysqli_stmt_execute($stmtD);
        }

        // Insertar nueva clave
        $sqlInsert = "INSERT INTO existencias(item, producto, cantidad, clave, fecha_subida)
                      VALUES(?,?,?,?,NOW())";

        $stmtI = mysqli_prepare($conexion, $sqlInsert);
        mysqli_stmt_bind_param($stmtI, "ssis", 
            $item, $producto, $cantidadSeparada, $claveNueva);
        mysqli_stmt_execute($stmtI);

        $mensaje = "✅ División aplicada correctamente.";
    }
}


/* -------------------------------------------------
   MOSTRAR EXISTENCIA
   ------------------------------------------------- */
$sql = "SELECT 
            item,
            producto,
            SUM(cantidad) AS cantidad,
            clave,
            MAX(fecha_subida) AS fecha_subida
        FROM existencias
        GROUP BY item, producto, clave
        ORDER BY producto ASC";

$result = mysqli_query($conexion, $sql);

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Existencia - Panel de Control</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body { background-color: #f8f9fa; }
    .sidebar {
      height: 100vh;
      background-color: #343a40;
      color: white;
      position: fixed;
      top: 0;
      left: 0;
      width: 220px;
      padding-top: 20px;
    }
    .sidebar a, .dropdown-btn {
      color: #ddd;
      display: block;
      padding: 10px 20px;
      text-decoration: none;
      background: none;
      border: none;
      width: 100%;
      text-align: left;
    }
    .sidebar a:hover, .dropdown-btn:hover {
      background-color: #495057;
      color: white;
    }
    .content {
      margin-left: 230px;
      padding: 20px;
    }
    .dropdown-container {
      display: block;
      padding-left: 20px;
    }
    tr.seleccion:hover {
        background-color: #cde3ff !important;
        cursor: pointer;
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <h4 class="text-center mb-4">📊 Panel</h4>
    <a href="panel.php">🏠 Inicio</a>

    <button class="dropdown-btn">📦 Inventario ▼</button>
    <div class="dropdown-container">
      <a href="inventario_subir.php">Subir CSV</a>
      <a href="existencia.php" class="fw-bold text-light">Existencia</a>
      <a href="subircargo.php">Subir Cargos</a>
      <a href="subirclaves.php">Subir Clave de Ventas</a>
      <a href="descargar_excel.php">📥 Descargar inventario en Excel</a>
    </div>

    <a href="#">⚙️ Configuración</a>
    <a href="logout.php" class="text-danger">🚪 Cerrar sesión</a>
  </div>

  <!-- Contenido -->
  <div class="content">
    <h3>📦 Existencia actual</h3>
    <hr>

    <div class="mb-3">
      <input type="text" id="buscador" class="form-control" placeholder="Buscar por item, nombre, cantidad o clave...">
    </div>

    <?php if ($mensaje): ?>
      <div class="alert alert-info"><?= $mensaje ?></div>
    <?php endif; ?>

    <table class='table table-striped table-bordered mt-4' id='tabla-existencias'>
        <thead class='table-dark'>
            <tr>
                <th>Item</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Clave</th>
                <th>Fecha subida</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($fila = mysqli_fetch_assoc($result)): ?>
            <tr class="seleccion"
                data-item="<?= $fila['item'] ?>"
                data-producto="<?= htmlspecialchars($fila['producto']) ?>"
                data-cantidad="<?= $fila['cantidad'] ?>"
                data-clave="<?= htmlspecialchars($fila['clave']) ?>"
            >
                <td><?= $fila["item"] ?></td>
                <td><?= htmlspecialchars($fila["producto"]) ?></td>
                <td class='fw-bold'><?= $fila["cantidad"] ?></td>
                <td><?= htmlspecialchars($fila["clave"]) ?></td>
                <td><?= htmlspecialchars($fila["fecha_subida"]) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
  </div>


<!-- MODAL -->
<div class="modal fade" id="modalDividir" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">

      <form method="POST">

        <div class="modal-header">
          <h5 class="modal-title">Dividir producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

            <input type="hidden" name="item_sel" id="item_sel">
            <input type="hidden" name="producto_sel" id="producto_sel">
            <input type="hidden" name="clave_sel" id="clave_sel">
            <input type="hidden" name="cantidad_sel" id="cantidad_sel">

            <div class="mb-2">
                <label>Producto</label>
                <input type="text" id="producto_read" class="form-control" disabled>
            </div>

            <div class="mb-2">
                <label>Clave actual</label>
                <input type="text" id="clave_read" class="form-control" disabled>
            </div>

            <div class="mb-2">
                <label>Cantidad disponible</label>
                <input type="text" id="cantidad_read" class="form-control" disabled>
            </div>

            <div class="mb-2">
                <label>Nueva clave</label>
                <input type="text" name="clave_nueva" class="form-control" required>
            </div>

            <div class="mb-2">
                <label>Cantidad para nueva clave</label>
                <input type="number" name="cantidad_clave" class="form-control" min="1" required>
            </div>

        </div>

        <div class="modal-footer">
          <button type="submit" name="dividir_confirmado" class="btn btn-primary">Aplicar</button>
        </div>

      </form>

    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.querySelectorAll(".seleccion").forEach(fila => {
    fila.addEventListener("click", () => {

        document.getElementById("item_sel").value = fila.dataset.item;
        document.getElementById("producto_sel").value = fila.dataset.producto;
        document.getElementById("clave_sel").value = fila.dataset.clave;
        document.getElementById("cantidad_sel").value = fila.dataset.cantidad;

        document.getElementById("producto_read").value = fila.dataset.producto;
        document.getElementById("clave_read").value = fila.dataset.clave;
        document.getElementById("cantidad_read").value = fila.dataset.cantidad;

        let modal = new bootstrap.Modal(document.getElementById("modalDividir"));
        modal.show();
    });
});

document.getElementById("buscador").addEventListener("keyup", function () {
    let filtro = this.value.toLowerCase();
    let filas = document.querySelectorAll("#tabla-existencias tbody tr");

    filas.forEach(fila => {
        let texto = fila.textContent.toLowerCase();
        fila.style.display = texto.includes(filtro) ? "" : "none";
    });
});
</script>

</body>
</html>
