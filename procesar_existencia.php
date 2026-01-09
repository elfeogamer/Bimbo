<?php
include "conexion.php";

if (isset($_POST["submit"])) {

    if ($_FILES["archivo"]["error"] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES["archivo"]["tmp_name"];
        $file = fopen($fileTmpPath, "r");

        // Saltar encabezados si los tiene
        fgetcsv($file);

        while (($columnas = fgetcsv($file, 10000, ",")) !== FALSE) {

            // Columnas del archivo
            $item = $columnas[5];        // Columna 6
            $producto = $columnas[6];    // Columna 7
            $cantidad = intval($columnas[7]); // Columna 8
            $clave = $columnas[9];       // Columna 10

            // Verificar si ya existe clave + producto
            $query = "SELECT cantidad FROM existencia WHERE clave = ? AND producto = ?";
            $stmt = $conexion->prepare($query);
            $stmt->bind_param("ss", $clave, $producto);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {

                $stmt->bind_result($cantidad_actual);
                $stmt->fetch();
                $nueva_cantidad = $cantidad_actual + $cantidad;

                // Actualizar suma
                $update = "UPDATE existencia SET cantidad = ? WHERE clave = ? AND producto = ?";
                $stmt2 = $conexion->prepare($update);
                $stmt2->bind_param("iss", $nueva_cantidad, $clave, $producto);
                $stmt2->execute();

            } else {

                // Insertar nuevo
                $insert = "INSERT INTO existencia (item, producto, cantidad, clave) VALUES (?, ?, ?, ?)";
                $stmt3 = $conexion->prepare($insert);
                $stmt3->bind_param("ssis", $item, $producto, $cantidad, $clave);
                $stmt3->execute();
            }

            $stmt->close();
        }

        fclose($file);
        echo "<script>alert('Archivo procesado correctamente'); window.location='existencia.php';</script>";
    } else {
        echo "Error al subir archivo.";
    }
}
?>
