<?php
session_start();
include '../../conexion.php'; 

// Control de acceso a la sesión
if (!isset($_SESSION['Usuario'])) {
    header("Location: ../index.php");
    exit();
}

$usuario = $_SESSION['Usuario'];
$rol = $_SESSION['Rol'];

// Variables para alertas estilizadas
$alerta_tipo = "";
$alerta_mensaje = "";
$redireccionar = "";

// ---------------- 1. ELIMINAR CLIENTE ----------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['eliminar_cliente'])) {
    $id = $_GET['eliminar_cliente'];

    if ($rol === 'Administrador') {
        $stmt = $conexion->prepare("DELETE FROM clientes WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $alerta_tipo = "success";
            $alerta_mensaje = "¡Cliente eliminado correctamente!";
        } else {
            $alerta_tipo = "error";
            $alerta_mensaje = "Error al eliminar cliente: " . $conexion->error;
        }
        $stmt->close();
        $redireccionar = "clientes.php";
    } else {
        $alerta_tipo = "warning";
        $alerta_mensaje = "Necesitas permisos de administrador para realizar esta acción.";
        $redireccionar = "clientes.php";
    }
}

// ---------------- 2. PROCESAR GUARDADO DE NUEVO CLIENTE ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_cliente'])) {
    $nombres = trim($_POST['nombres']);
    $documento = trim($_POST['documento']);
    $telefono = trim($_POST['telefono']);
    $correo = trim($_POST['correo']);
    $direccion = trim($_POST['direccion']);
    $ciudad = trim($_POST['ciudad']);
    $observaciones = trim($_POST['observaciones']);

    if (!empty($nombres) && !empty($documento) && !empty($telefono)) {
        $stmt = $conexion->prepare("INSERT INTO clientes (nombres, documento, telefono, correo, direccion, ciudad, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $nombres, $documento, $telefono, $correo, $direccion, $ciudad, $observaciones);
        
        if ($stmt->execute()) {
            $alerta_tipo = "success";
            $alerta_mensaje = "¡Cliente registrado correctamente!";
        } else {
            $alerta_tipo = "error";
            $alerta_mensaje = "Error al registrar cliente: " . $conexion->error;
        }
        $stmt->close();
        $redireccionar = "clientes.php";
    } else {
        $alerta_tipo = "warning";
        $alerta_mensaje = "Por favor completa los campos obligatorios (Nombres, Documento, Teléfono).";
    }
}

// ---------------- 3. PROCESAR ACTUALIZACIÓN DE CLIENTE ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_cliente'])) {
    $id = $_POST['id'];
    $nombres = trim($_POST['nombres']);
    $documento = trim($_POST['documento']);
    $telefono = trim($_POST['telefono']);
    $correo = trim($_POST['correo']);
    $direccion = trim($_POST['direccion']);
    $ciudad = trim($_POST['ciudad']);
    $observaciones = trim($_POST['observaciones']);

    if (!empty($nombres) && !empty($documento) && !empty($telefono)) {
        $stmt = $conexion->prepare("UPDATE clientes SET nombres=?, documento=?, telefono=?, correo=?, direccion=?, ciudad=?, observaciones=? WHERE id=?");
        $stmt->bind_param("sssssssi", $nombres, $documento, $telefono, $correo, $direccion, $ciudad, $observaciones, $id);
        
        if ($stmt->execute()) {
            $alerta_tipo = "success";
            $alerta_mensaje = "¡Cliente actualizado correctamente!";
        } else {
            $alerta_tipo = "error";
            $alerta_mensaje = "Error al actualizar cliente: " . $conexion->error;
        }
        $stmt->close();
        $redireccionar = "clientes.php";
    } else {
        $alerta_tipo = "warning";
        $alerta_mensaje = "Por favor completa los campos obligatorios (Nombres, Documento, Teléfono).";
    }
} 

// ---------------- 4. CARGAR DATOS PARA EDICIÓN ----------------
$editando = false;
$edit_id = $edit_nombres = $edit_documento = $edit_telefono = $edit_correo = $edit_direccion = $edit_ciudad = $edit_observaciones = "";

if (isset($_GET['editar_id'])) {
    $id_editar = $_GET['editar_id'];
    $stmt = $conexion->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($cliente_edit = $res->fetch_assoc()) {
        $editando = true;
        $edit_id = $cliente_edit['id'];
        $edit_nombres = $cliente_edit['nombres'];
        $edit_documento = $cliente_edit['documento'];
        $edit_telefono = $cliente_edit['telefono'];
        $edit_correo = $cliente_edit['correo'];
        $edit_direccion = $cliente_edit['direccion'];
        $edit_ciudad = $cliente_edit['ciudad'];
        $edit_observaciones = $cliente_edit['observaciones'];
    }
    $stmt->close();
}

// ---------------- CONSULTAS DE ESTADÍSTICAS ----------------
$qTotal = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM clientes");
$totalClientes = mysqli_fetch_assoc($qTotal)['total'] ?? 0;

$qNuevos = mysqli_query($conexion, "SELECT COUNT(*) AS nuevos FROM clientes WHERE MONTH(fecha_registro) = MONTH(CURRENT_DATE()) AND YEAR(fecha_registro) = YEAR(CURRENT_DATE())");
$nuevosMes = mysqli_fetch_assoc($qNuevos)['nuevos'] ?? 0;


?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GLASÉ | Clientes</title>
    <link rel="stylesheet" href="../../css/clientes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- SweetAlert2 para notificaciones con estilo del dashboard -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

<?php if (!empty($alerta_mensaje)): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        Swal.fire({
            icon: '<?php echo $alerta_tipo; ?>',
            title: '<?php echo ($alerta_tipo === "success") ? "¡Éxito!" : (($alerta_tipo === "warning") ? "Atención" : "Error"); ?>',
            text: '<?php echo $alerta_mensaje; ?>',
            confirmButtonColor: '#6D4C41',
            confirmButtonText: 'Aceptar'
        }).then(() => {
            <?php if (!empty($redireccionar)): ?>
                window.location.href = '<?php echo $redireccionar; ?>';
            <?php endif; ?>
        });
    });
</script>
<?php endif; ?>

<!-------------------- SIDEBAR -------------------->
<aside class="sidebar">
    <div class="logo">
        <i class="fa-solid fa-shoe-prints"></i>
        <h2>GLASÉ</h2>
    </div>

    <ul>
        <li>
            <a href="../dashboard.php">
                <i class="fa-solid fa-house"></i>
                Inicio
            </a>
        </li>
        <li>
            <a href="arreglos.php">
                <i class="fa-solid fa-screwdriver-wrench"></i>
                Arreglos
            </a>
        </li>
        <li>
            <a href="materiales.php">
                <i class="fa-solid fa-boxes-stacked"></i>
                Materiales
            </a>
        </li>

        <li>
            <a href="mercancia.php">
                <i class="fa-solid fa-box-open"></i>
                Mercancía
            </a>
        </li>
        <li class="active">
            <a href="clientes.php">
                <i class="fa-solid fa-users"></i>
                Clientes
            </a>
        </li>
   
        <li>
            <a href="configuracion.php">
                <i class="fa-solid fa-gear"></i>
                Configuración
            </a>
        </li>
        <li>
            <a href="../cerrarsesion.php">
                <i class="fa-solid fa-right-from-bracket"></i>
                Cerrar Sesión
            </a>
        </li>
    </ul>
</aside>

<!-------------------- MAIN -------------------->
<main>

<header>
    <div>
        <h1>Gestión de Clientes</h1>
        <p>Administra la información de todos los clientes registrados.</p>
    </div>
</header>

<!-------------------- FORMULARIO (CREAR O EDITAR) -------------------->
<section class="formulario">

    <h2><?php echo $editando ? 'Editar Cliente' : 'Registrar Cliente'; ?></h2>

    <form action="clientes.php" method="POST">

        <?php if ($editando): ?>
            <input type="hidden" name="id" value="<?php echo $edit_id; ?>">
        <?php endif; ?>

        <div class="fila">
            <div class="grupo">
                <label>Nombres *</label>
                <input type="text" name="nombres" placeholder="Nombre completo" value="<?php echo htmlspecialchars($edit_nombres); ?>" required>
            </div>

            <div class="grupo">
                <label>Documento *</label>
                <input type="text" name="documento" placeholder="Número de documento" value="<?php echo htmlspecialchars($edit_documento); ?>" required>
            </div>
        </div>

        <div class="fila">
            <div class="grupo">
                <label>Teléfono *</label>
                <input type="text" name="telefono" placeholder="Número telefónico" value="<?php echo htmlspecialchars($edit_telefono); ?>" required>
            </div>

            <div class="grupo">
                <label>Correo Electrónico</label>
                <input type="email" name="correo" placeholder="Correo" value="<?php echo htmlspecialchars($edit_correo); ?>">
            </div>
        </div>

        <div class="fila">
            <div class="grupo">
                <label>Dirección</label>
                <input type="text" name="direccion" placeholder="Dirección" value="<?php echo htmlspecialchars($edit_direccion); ?>">
            </div>

            <div class="grupo">
                <label>Ciudad</label>
                <input type="text" name="ciudad" placeholder="Ciudad" value="<?php echo htmlspecialchars($edit_ciudad); ?>">
            </div>
        </div>

        <div class="grupo">
            <label>Observaciones</label>
            <textarea name="observaciones" rows="4" placeholder="Información adicional del cliente..."><?php echo htmlspecialchars($edit_observaciones); ?></textarea>
        </div>

        <div class="botones">
            <?php if ($editando): ?>
                <button type="submit" name="editar_cliente" class="guardar">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Actualizar
                </button>

                <a href="clientes.php" class="cancelar">
                    <i class="fa-solid fa-xmark"></i>
                    Cancelar
                </a>
            <?php else: ?>
                <button type="submit" name="guardar_cliente" class="guardar">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Guardar
                </button>

                <button type="reset" class="cancelar">
                    <i class="fa-solid fa-trash"></i>
                    Limpiar
                </button>
            <?php endif; ?>
        </div>
    </form>

</section>

<!-------------------- ESTADÍSTICAS -------------------->
<section class="cards">
    <div class="card">
        <i class="fa-solid fa-users"></i>
        <h2><?php echo $totalClientes; ?></h2>
        <p>Clientes Registrados</p>
    </div>

   

    <div class="card">
        <i class="fa-solid fa-user-plus"></i>
        <h2><?php echo $nuevosMes; ?></h2>
        <p>Nuevos este mes</p>
    </div>
</section>

<!-------------------- TABLA -------------------->
<section class="tabla">

    <div class="encabezado">
        <h2>Listado de Clientes</h2>
        <input type="text" id="buscar" placeholder="Buscar cliente...">
    </div>

    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Documento</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>Ciudad</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>
            <?php
            $sql = "SELECT * FROM clientes ORDER BY id DESC";
            $resultado = mysqli_query($conexion, $sql);

            if ($resultado && mysqli_num_rows($resultado) > 0) {
                while ($cliente = mysqli_fetch_assoc($resultado)) {
            ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cliente['nombres']); ?></td>
                        <td><?php echo htmlspecialchars($cliente['documento']); ?></td>
                        <td><?php echo htmlspecialchars($cliente['telefono']); ?></td>
                        <td><?php echo htmlspecialchars($cliente['correo']); ?></td>
                        <td><?php echo htmlspecialchars($cliente['ciudad']); ?></td>
                        <td>
                            <!-- BOTÓN DE EDITAR -->
                            <a href="clientes.php?editar_id=<?php echo $cliente['id']; ?>" class="editar" title="Editar">
                                <i class="fa-solid fa-pen"></i>
                            </a>

                            <!-- BOTÓN DE ELIMINAR CON ESTILO NUEVO -->
                            <?php if ($rol === 'Administrador'): ?>
                                <a href="#" onclick="confirmarEliminacion(<?php echo $cliente['id']; ?>)" class="eliminar" title="Eliminar">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
            <?php
                }
            } else {
                echo "<tr><td colspan='6' style='text-align:center;'>No hay clientes registrados en la base de datos.</td></tr>";
            }
            ?>
        </tbody>
    </table>

</section>

</main>

<script src="../../js/clientes.js"></script>
<script>
function confirmarEliminacion(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "¡No podrás revertir esta acción!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#6D4C41',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'clientes.php?eliminar_cliente=' + id;
        }
    })
}
</script>

</body>
</html>