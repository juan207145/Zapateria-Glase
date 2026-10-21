<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../../conexion.php'; 

// Control de acceso a la sesión
if (!isset($_SESSION['Usuario'])) {
    header("Location: ../../index.php");
    exit();
}

$usuario = $_SESSION['Usuario'];
$rol = $_SESSION['Rol'];

// Variables para alertas estilizadas
$alerta_tipo = "";
$alerta_mensaje = "";
$redireccionar = "";

// Asegurar que exista la tabla de historial de materiales
@mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS historial_materiales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    detalle VARCHAR(255) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ---------------- 1. ELIMINAR MATERIAL ----------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['eliminar_materiales'])) {
    $id = $_GET['eliminar_materiales'];

    if ($rol === 'Administrador' || $rol === 'administrador') {
        $consulta_mate = $conexion->prepare("SELECT nombredelmaterial FROM materiales WHERE id = ?");
        $consulta_mate->bind_param("i", $id);
        $consulta_mate->execute();
        $res_mate = $consulta_mate->get_result();
        $nombre_eliminado = "Material ID " . $id;
        if ($p_dat = $res_mate->fetch_assoc()) {
            $nombre_eliminado = $p_dat['nombredelmaterial'];
        }
        $consulta_mate->close();

        $stmt = $conexion->prepare("DELETE FROM materiales WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $accion_reciente = "<strong>Eliminado:</strong> Se retiró del inventario " . htmlspecialchars($nombre_eliminado) . ".";
            @mysqli_query($conexion, "INSERT INTO historial_materiales (detalle, tipo) VALUES ('$accion_reciente', 'eliminado')");

            $alerta_tipo = "success";
            $alerta_mensaje = "¡Material eliminado correctamente!";
        } else {
            $alerta_tipo = "error";
            $alerta_mensaje = "Error al eliminar material: " . $conexion->error;
        }
        $stmt->close();
        $redireccionar = "materiales.php";
    } else {
        $alerta_tipo = "warning";
        $alerta_mensaje = "Necesitas permisos de administrador para realizar esta acción.";
        $redireccionar = "materiales.php";
    }
}

// ---------------- 2. PROCESAR GUARDADO DE NUEVO MATERIAL ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_materiales'])) {
    $codigo = trim($_POST['codigo']);
    $nombredelmaterial = trim($_POST['nombredelmaterial']);
    $categoria = trim($_POST['categoria']);
    $cantidad = trim($_POST['cantidad']);
    $unidad = trim($_POST['unidad']);
    $proveedor = trim($_POST['proveedor']);

    if (!empty($nombredelmaterial) && !empty($codigo) && !empty($categoria) && !empty($cantidad) && !empty($unidad) && !empty($proveedor)) {
        $stmt = $conexion->prepare("INSERT INTO materiales (codigo, nombredelmaterial, categoria, cantidad, unidad, proveedor) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $codigo, $nombredelmaterial, $categoria, $cantidad, $unidad, $proveedor);
        
        if ($stmt->execute()) {
            $accion_reciente = "<strong>Agregado:</strong> Ingreso de nuevo material " . htmlspecialchars($nombredelmaterial) . ".";
            @mysqli_query($conexion, "INSERT INTO historial_materiales (detalle, tipo) VALUES ('$accion_reciente', 'agregado')");

            $alerta_tipo = "success";
            $alerta_mensaje = "¡Material registrado correctamente!";
        } else {
            $alerta_tipo = "error";
            $alerta_mensaje = "Error al registrar material: " . $conexion->error;
        }
        $stmt->close();
        $redireccionar = "materiales.php";
    } else {
        $alerta_tipo = "warning";
        $alerta_mensaje = "Por favor completa todos los campos obligatorios.";
    }
}

// ---------------- 3. PROCESAR ACTUALIZACIÓN DE MATERIAL ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_material'])) {
    $id = $_POST['id'];
    $codigo = trim($_POST['codigo']);
    $nombredelmaterial = trim($_POST['nombredelmaterial']);
    $categoria = trim($_POST['categoria']);
    $cantidad = trim($_POST['cantidad']);
    $unidad = trim($_POST['unidad']);
    $proveedor = trim($_POST['proveedor']);
   
    if (!empty($nombredelmaterial) && !empty($codigo) && !empty($categoria) && !empty($cantidad) && !empty($unidad) && !empty($proveedor)) {
        $stmt = $conexion->prepare("UPDATE materiales SET codigo=?, nombredelmaterial=?, categoria=?, cantidad=?, unidad=?, proveedor=? WHERE id=?");
        $stmt->bind_param("ssssssi", $codigo, $nombredelmaterial, $categoria, $cantidad, $unidad, $proveedor, $id);

        if ($stmt->execute()) {
            $accion_reciente = "<strong>Editado:</strong> Modificación en " . htmlspecialchars($nombredelmaterial) . ".";
            @mysqli_query($conexion, "INSERT INTO historial_materiales (detalle, tipo) VALUES ('$accion_reciente', 'editado')");

            $alerta_tipo = "success";
            $alerta_mensaje = "¡Material actualizado correctamente!";
        } else {
            $alerta_tipo = "error";
            $alerta_mensaje = "Error al actualizar material: " . $conexion->error;
        }
        $stmt->close();
        $redireccionar = "materiales.php";
    } else {
        $alerta_tipo = "warning";
        $alerta_mensaje = "Por favor completa todos los campos obligatorios.";
    }
} 

// ---------------- 4. CARGAR DATOS PARA EDICIÓN ----------------
$editando = false;
$edit_id = $edit_codigo = $edit_nombredelmaterial = $edit_categoria = $edit_cantidad = $edit_unidad = $edit_proveedor = "";

if (isset($_GET['editar_id'])) {
    $id_editar = $_GET['editar_id'];
    $stmt = $conexion->prepare("SELECT * FROM materiales WHERE id = ?");
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($material_edit = $res->fetch_assoc()) {
        $editando = true;
        $edit_id = $material_edit['id'];
        $edit_codigo = $material_edit['codigo'];
        $edit_nombredelmaterial = $material_edit['nombredelmaterial'];
        $edit_categoria = $material_edit['categoria'];
        $edit_cantidad = $material_edit['cantidad'];
        $edit_unidad = $material_edit['unidad'];
        $edit_proveedor = $material_edit['proveedor'];
    }
    $stmt->close();
}

// ---------------- 5. CONSULTAS DE ESTADÍSTICAS ----------------
$totalmateriales = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM materiales");
$materialesregistrados = mysqli_fetch_assoc($totalmateriales)['total'] ?? 0;

$materialesporagotarse = mysqli_query($conexion, "SELECT COUNT(*) AS por_agotarse FROM materiales WHERE cantidad < 5");
$materialesagotarse = mysqli_fetch_assoc($materialesporagotarse)['por_agotarse'] ?? 0;

$resultado_materiales = mysqli_query($conexion, "SELECT * FROM materiales ORDER BY id DESC");
$resultado_historial = mysqli_query($conexion, "SELECT * FROM historial_materiales ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GLASÉ | Materiales</title>
    <link rel="stylesheet" href="../../css/materiales.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .formulario-oculto {
            display: none;
        }
        .movimientos li i.fa-circle-plus { color: #2ecc71; }
        .movimientos li i.fa-pen-to-square { color: #3498db; }
        .movimientos li i.fa-trash-can { color: #e74c3c; }
    </style>
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
                Panel Principal
            </a>
        </li>
        <li>
            <a href="arreglos.php">
                <i class="fa-solid fa-screwdriver-wrench"></i>
                Arreglos
            </a>
        </li>
        <li class="active">
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
        <li>
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
            <a href="../../cerrarsesion.php">
                <i class="fa-solid fa-right-from-bracket"></i>
                Cerrar Sesión
            </a>
        </li>
    </ul>
</aside>

<!-------------------- CONTENIDO -------------------->
<main>
<header>
    <div>
        <h1>Gestión de Materiales</h1>
        <p>Administración de insumos y materiales para la fabricación y reparación.</p>
    </div>
    <button class="btnAgregar" onclick="toggleFormulario()">
        <i class="fa-solid fa-plus"></i>
        Agregar Material
    </button>
</header>

<!-------------------- FORMULARIO (CREAR O EDITAR) -------------------->
<section class="formulario <?php echo $editando ? '' : 'formulario-oculto'; ?>">
    <h2><?php echo $editando ? 'Editar Material' : 'Registrar Nuevo Material'; ?></h2>
    <form action="materiales.php" method="POST">
        <?php if ($editando): ?>
            <input type="hidden" name="id" value="<?php echo $edit_id; ?>">
        <?php endif; ?>

        <div class="fila">
            <div class="grupo">
                <label>Código *</label>
                <input type="text" name="codigo" placeholder="Ej: MAT001" value="<?php echo htmlspecialchars($edit_codigo); ?>" required>
            </div>
            <div class="grupo">
                <label>Nombre del Material *</label>
                <input type="text" name="nombredelmaterial" placeholder="Ej: Pegante Amarillo" value="<?php echo htmlspecialchars($edit_nombredelmaterial); ?>" required>
            </div>
        </div>

        <div class="fila">
            <div class="grupo">
                <label>Categoría *</label>
                <select name="categoria" required>
                    <option value="" disabled selected>Seleccione categoría</option>
                    <option value="Suelas" <?php if($edit_categoria=='Suelas') echo 'selected'; ?>>Suelas</option>
                    <option value="Pegantes" <?php if($edit_categoria=='Pegantes') echo 'selected'; ?>>Pegantes</option>
                    <option value="Hilos" <?php if($edit_categoria=='Hilos') echo 'selected'; ?>>Hilos</option>
                    <option value="Plantillas" <?php if($edit_categoria=='Plantillas') echo 'selected'; ?>>Plantillas</option>
                    <option value="Accesorios" <?php if($edit_categoria=='Accesorios') echo 'selected'; ?>>Accesorios</option>
                </select>
            </div>
            <div class="grupo">
                <label>Cantidad *</label>
                <input type="number" name="cantidad" placeholder="Ej: 25" value="<?php echo htmlspecialchars($edit_cantidad); ?>" required>
            </div>
        </div>

        <div class="fila">
            <div class="grupo">
                <label>Unidad *</label>
                <input type="text" name="unidad" placeholder="Ej: Tarros / Pares / Rollos" value="<?php echo htmlspecialchars($edit_unidad); ?>" required>
            </div>
            <div class="grupo">
                <label>Proveedor *</label>
                <input type="text" name="proveedor" placeholder="Ej: Proveedor S.A." value="<?php echo htmlspecialchars($edit_proveedor); ?>" required>
            </div>
        </div>

        <div class="botones">
            <?php if ($editando): ?>
                <button type="submit" name="editar_material" class="guardar">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Actualizar
                </button>
                <a href="materiales.php" class="cancelar">
                    <i class="fa-solid fa-xmark"></i>
                    Cancelar
                </a>
            <?php else: ?>
                <button type="submit" name="guardar_materiales" class="guardar">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Guardar
                </button>
                <button type="button" class="cancelar" onclick="toggleFormulario()">
                    <i class="fa-solid fa-xmark"></i>
                    Cancelar
                </button>
            <?php endif; ?>
        </div>
    </form>
</section>

<!-------------------- TARJETAS -------------------->
<section class="cards" style="grid-template-columns: repeat(2, 1fr);">
    <div class="card">
        <i class="fa-solid fa-boxes-stacked"></i>
        <div>
            <h2><?php echo $materialesregistrados; ?></h2>
            <p>Materiales Registrados</p>
        </div>
    </div>
    <div class="card">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div>
            <h2><?php echo $materialesagotarse; ?></h2>
            <p>Stock Bajo</p>
        </div>
    </div>
</section>

<!-------------------- BUSCADOR Y FILTRO -------------------->
<section class="busqueda" style="display: flex; gap: 20px; margin-bottom: 20px;">
    <div class="buscar" style="flex: 1; display: flex; align-items: center; background: white; padding: 10px 15px; border-radius: 10px; box-shadow: var(--sombra);">
        <i class="fa-solid fa-magnifying-glass" style="color: var(--gris); margin-right: 10px;"></i>
        <input type="text" id="buscar" placeholder="Buscar material..." style="border: none; outline: none; width: 100%; font-size: 14px;">
    </div>
    <select id="filtroCategoria" style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 10px; background: white; color: var(--texto); outline: none; cursor: pointer;">
        <option value="todos">Todos</option>
        <option value="Suelas">Suelas</option>
        <option value="Pegantes">Pegantes</option>
        <option value="Hilos">Hilos</option>
        <option value="Plantillas">Plantillas</option>
        <option value="Accesorios">Accesorios</option>
    </select>
</section>

<!-------------------- TABLA -------------------->
<section class="panel">
<div class="titulo">
    <h2>Listado de Materiales</h2>
</div>
<table>
<thead>
    <tr>
        <th>Código</th>
        <th>Material</th>
        <th>Categoría</th>
        <th>Cantidad</th>
        <th>Unidad</th>
        <th>Estado</th>
        <th>Proveedor</th>
        <th>Acciones</th>
    </tr>
</thead>
<tbody id="tablaMateriales">
    <?php if ($resultado_materiales && mysqli_num_rows($resultado_materiales) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($resultado_materiales)): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['codigo']); ?></td>
                <td><?php echo htmlspecialchars($row['nombredelmaterial']); ?></td>
                <td><?php echo htmlspecialchars($row['categoria']); ?></td>
                <td><?php echo htmlspecialchars($row['cantidad']); ?></td>
                <td><?php echo htmlspecialchars($row['unidad']); ?></td>
                <td>
                    <?php if ($row['cantidad'] < 5): ?>
                        <span class="estado bajo">Stock Bajo</span>
                    <?php else: ?>
                        <span class="estado disponible">Disponible</span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($row['proveedor']); ?></td>
    
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="8" style="text-align: center;">No hay materiales registrados en el inventario.</td>
        </tr>
    <?php endif; ?>
</tbody>
</table>
</section>

<!-------------------- PANEL INFERIOR -------------------->
<section class="dashboard-grid">
<div class="panel">
<div class="titulo">
    <h2>Movimientos Recientes</h2>
</div>
<ul class="movimientos">
    <?php if ($resultado_historial && mysqli_num_rows($resultado_historial) > 0): ?>
        <?php while($hist = mysqli_fetch_assoc($resultado_historial)): ?>
            <li>
                <?php if ($hist['tipo'] == 'eliminado'): ?>
                    <i class="fa-solid fa-trash-can"></i>
                <?php elseif ($hist['tipo'] == 'editado'): ?>
                    <i class="fa-solid fa-pen-to-square"></i>
                <?php else: ?>
                    <i class="fa-solid fa-circle-plus"></i>
                <?php endif; ?>
                <span><?php echo $hist['detalle']; ?></span>
            </li>
        <?php endwhile; ?>
    <?php else: ?>
        <li><i class="fa-solid fa-circle-plus"></i> <span>Ingreso reciente de insumos.</span></li>
    <?php endif; ?>
</ul>
</div>

<div class="panel">
<div class="titulo">
    <h2>Materiales por Agotarse</h2>
</div>
<ul class="stock" style="list-style:none; padding:0;">
    <?php 
    $res_bajo = mysqli_query($conexion, "SELECT nombredelmaterial, cantidad FROM materiales WHERE cantidad < 5");
    if ($res_bajo && mysqli_num_rows($res_bajo) > 0):
        while($bajo = mysqli_fetch_assoc($res_bajo)):
    ?>
        <li style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #eee;">
            <span><?php echo htmlspecialchars($bajo['nombredelmaterial']); ?></span>
            <span style="color:#e74c3c; font-weight:600;"><?php echo $bajo['cantidad']; ?></span>
        </li>
    <?php 
        endwhile;
    else:
    ?>
        <li style="padding:10px 0; color:#777;">No hay materiales con stock crítico.</li>
    <?php endif; ?>
</ul>
</div>
</section>

<!-------------------- FOOTER -------------------->
<footer>
<p>© 2026 Sistema GLASÉ | Gestión Integral de Inventario</p>
</footer>
</main>

<script>
function toggleFormulario() {
    const form = document.querySelector('.formulario');
    form.classList.toggle('formulario-oculto');
    if (!form.classList.contains('formulario-oculto')) {
        form.scrollIntoView({ behavior: 'smooth' });
    }
}

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
            window.location.href = 'materiales.php?eliminar_materiales=' + id;
        }
    })
}

// BUSCADOR Y FILTRO DE CATEGORÍA EN TIEMPO REAL
document.addEventListener("DOMContentLoaded", () => {
    const inputBuscar = document.getElementById("buscar");
    const selectCategoria = document.getElementById("filtroCategoria");
    const filas = document.querySelectorAll("#tablaMateriales tr");

    function filtrarTabla() {
        const textoBusqueda = inputBuscar ? inputBuscar.value.toLowerCase().trim() : "";
        const categoriaSeleccionada = selectCategoria ? selectCategoria.value.toLowerCase().trim() : "todos";

        filas.forEach(fila => {
            const celdas = fila.getElementsByTagName("td");
            if (celdas.length < 3) return;

            const textoFila = fila.textContent.toLowerCase();
            const categoriaFila = celdas[2].textContent.toLowerCase().trim();

            const coincideTexto = textoFila.includes(textoBusqueda);
            const coincideCategoria = (categoriaSeleccionada === "todos") || (categoriaFila === categoriaSeleccionada);

            if (coincideTexto && coincideCategoria) {
                fila.style.display = "";
            } else {
                fila.style.display = "none";
            }
        });
    }

    if (inputBuscar) {
        inputBuscar.addEventListener("keyup", filtrarTabla);
    }
    if (selectCategoria) {
        selectCategoria.addEventListener("change", filtrarTabla);
    }
});
</script>
</body>
</html>