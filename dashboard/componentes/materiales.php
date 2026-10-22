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
$rol = $_SESSION['Rol'] ?? '';
$es_admin = (strtolower($rol) === 'administrador');

// Variables para alertas estilizadas
$alerta_tipo = "";
$alerta_mensaje = "";
$redireccionar = "";

// Categorías disponibles (una sola fuente para el formulario y el filtro)
$categorias = ['Suelas', 'Pegantes', 'Hilos', 'Plantillas', 'Accesorios'];

// Registra un movimiento en el historial (si la tabla falla, no rompe la página)
function registrar_historial($conexion, $detalle, $tipo) {
    $stmt = @$conexion->prepare("INSERT INTO historial_materiales (detalle, tipo) VALUES (?, ?)");
    if ($stmt) {
        $stmt->bind_param("ss", $detalle, $tipo);
        @$stmt->execute();
        $stmt->close();
    }
}

// ---------------- 1. ELIMINAR MATERIAL ----------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['eliminar_material'])) {
    $id = (int) $_GET['eliminar_material'];

    if ($es_admin) {
        // Obtener el nombre antes de borrar, para el historial
        $nombre_eliminado = "ID " . $id;
        $stmtNombre = $conexion->prepare("SELECT nombredelmaterial FROM materiales WHERE id = ?");
        $stmtNombre->bind_param("i", $id);
        $stmtNombre->execute();
        $resNombre = $stmtNombre->get_result();
        if ($filaNombre = $resNombre->fetch_assoc()) {
            $nombre_eliminado = $filaNombre['nombredelmaterial'];
        }
        $stmtNombre->close();

        $stmt = $conexion->prepare("DELETE FROM materiales WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            registrar_historial($conexion, "Se retiró del inventario " . $nombre_eliminado . ".", 'eliminado');
            $alerta_tipo = "success";
            $alerta_mensaje = "¡Material eliminado correctamente!";
        } else {
            $alerta_tipo = "error";
            $alerta_mensaje = "Error al eliminar: " . $conexion->error;
        }
        $stmt->close();
        $redireccionar = "materiales.php";
    } else {
        $alerta_tipo = "warning";
        $alerta_mensaje = "Necesitas permisos de administrador.";
        $redireccionar = "materiales.php";
    }
}

// ---------------- 2. PROCESAR GUARDADO DE NUEVO MATERIAL ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_material'])) {
    $codigo = trim($_POST['codigo'] ?? '');
    $nombredelmaterial = trim($_POST['nombredelmaterial'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $cantidad = trim($_POST['cantidad'] ?? '');
    $unidad = trim($_POST['unidad'] ?? '');
    $proveedor = trim($_POST['proveedor'] ?? '');

    // Se valida cantidad con is_numeric para permitir el valor 0
    if ($codigo !== '' && $nombredelmaterial !== '' && $categoria !== '' && $cantidad !== '' && is_numeric($cantidad) && $unidad !== '' && $proveedor !== '') {
        $cantidad = (int) $cantidad;

        $stmt = $conexion->prepare("INSERT INTO materiales (codigo, nombredelmaterial, categoria, cantidad, unidad, proveedor) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiss", $codigo, $nombredelmaterial, $categoria, $cantidad, $unidad, $proveedor);

        if ($stmt->execute()) {
            registrar_historial($conexion, "Ingreso de nuevo material: " . $nombredelmaterial . ".", 'agregado');
            $alerta_tipo = "success";
            $alerta_mensaje = "¡Material registrado correctamente!";
        } else {
            $alerta_tipo = "error";
            $alerta_mensaje = "Error SQL al guardar: " . $conexion->error;
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
    $id = (int) ($_POST['id'] ?? 0);
    $codigo = trim($_POST['codigo'] ?? '');
    $nombredelmaterial = trim($_POST['nombredelmaterial'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $cantidad = trim($_POST['cantidad'] ?? '');
    $unidad = trim($_POST['unidad'] ?? '');
    $proveedor = trim($_POST['proveedor'] ?? '');

    if ($id > 0 && $codigo !== '' && $nombredelmaterial !== '' && $categoria !== '' && $cantidad !== '' && is_numeric($cantidad) && $unidad !== '' && $proveedor !== '') {
        $cantidad = (int) $cantidad;

        $stmt = $conexion->prepare("UPDATE materiales SET codigo=?, nombredelmaterial=?, categoria=?, cantidad=?, unidad=?, proveedor=? WHERE id=?");
        // Tipos: s s s i s s i  (CORREGIDO: antes era "sssisis")
        $stmt->bind_param("sssissi", $codigo, $nombredelmaterial, $categoria, $cantidad, $unidad, $proveedor, $id);

        if ($stmt->execute()) {
            registrar_historial($conexion, "Modificación en " . $nombredelmaterial . ".", 'editado');
            $alerta_tipo = "success";
            $alerta_mensaje = "¡Material actualizado correctamente!";
        } else {
            $alerta_tipo = "error";
            $alerta_mensaje = "Error SQL al actualizar: " . $conexion->error;
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
    $id_editar = (int) $_GET['editar_id'];
    $stmt = $conexion->prepare("SELECT * FROM materiales WHERE id = ?");
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($material_edit = $res->fetch_assoc()) {
        $editando = true;
        // CORREGIDO: la columna se llama "Id" en la BD; se acepta "Id" o "id"
        $edit_id = $material_edit['Id'] ?? $material_edit['id'] ?? '';
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
$resultado_proveedores = mysqli_query($conexion, "SELECT DISTINCT proveedor FROM materiales WHERE proveedor <> '' ORDER BY proveedor ASC");
$resultado_historial = mysqli_query($conexion, "SELECT * FROM historial_materiales ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GLASÉ | Materiales</title>
    <link rel="stylesheet" href="/Zapateria-Glase/css/materiales.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .formulario-oculto { display: none; }
        .movimientos li i.fa-circle-plus { color: #2ecc71; }
        .movimientos li i.fa-pen-to-square { color: #3498db; }
        .movimientos li i.fa-trash-can { color: #e74c3c; }

        /* Proveedores + Últimos movimientos */
        .paneles-inferiores {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-top: 25px;
        }
        .paneles-inferiores .caja {
            background: #fff;
            border-radius: 20px;
            padding: 28px 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            overflow-x: auto;
        }
        .paneles-inferiores .caja h2 {
            color: #6D4C41;
            font-size: 20px;
            margin-bottom: 20px;
        }
        .tabla-proveedores { width: 100%; border-collapse: collapse; }
        .tabla-proveedores th {
            background: #f7f7f7;
            color: #6D4C41;
            text-align: left;
            padding: 14px 15px;
            border-bottom: 2px solid #eee;
        }
        .tabla-proveedores td {
            padding: 14px 15px;
            border-bottom: 1px solid #eee;
        }
        .tabla-proveedores td i { color: #6D4C41; margin-right: 10px; }
        .movimientos { list-style: none; margin: 0; padding: 0; }
        .movimientos li {
            padding: 9px 0;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .movimientos li strong { margin-right: 4px; }
        @media (max-width: 900px) {
            .paneles-inferiores { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>

<?php if (!empty($alerta_mensaje)): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        Swal.fire({
            icon: <?php echo json_encode($alerta_tipo); ?>,
            title: <?php echo json_encode(($alerta_tipo === "success") ? "¡Éxito!" : (($alerta_tipo === "warning") ? "Atención" : "Error")); ?>,
            text: <?php echo json_encode($alerta_mensaje); ?>,
            confirmButtonColor: '#6D4C41',
            confirmButtonText: 'Aceptar'
        }).then(() => {
            <?php if (!empty($redireccionar)): ?>
                window.location.href = <?php echo json_encode($redireccionar); ?>;
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
        <li><a href="../dashboard.php"><i class="fa-solid fa-house"></i> Panel Principal</a></li>
        <li><a href="arreglos.php"><i class="fa-solid fa-screwdriver-wrench"></i> Arreglos</a></li>
        <li class="active"><a href="materiales.php"><i class="fa-solid fa-boxes-stacked"></i> Materiales</a></li>
        <li><a href="mercancia.php"><i class="fa-solid fa-box-open"></i> Mercancía</a></li>
        <li><a href="clientes.php"><i class="fa-solid fa-users"></i> Clientes</a></li>
        <li><a href="configuracion.php"><i class="fa-solid fa-gear"></i> Configuración</a></li>
        <li><a href="../cerrarsesion.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a></li>
    </ul>
</aside>

<!-------------------- CONTENIDO -------------------->
<main>
<header>
    <div>
        <h1>Materiales</h1>
        <p>Control y administración de materiales utilizados en la zapatería.</p>
    </div>
    <button class="btnAgregar" onclick="toggleFormulario()">
        <i class="fa-solid fa-plus"></i> Agregar Material
    </button>
</header>

<!-------------------- FORMULARIO (CREAR O EDITAR) -------------------->
<section class="formulario <?php echo $editando ? '' : 'formulario-oculto'; ?>">
    <h2><?php echo $editando ? 'Editar Material' : 'Registrar Material'; ?></h2>
    <form action="materiales.php" method="POST">
        <?php if ($editando): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_id); ?>">
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
                    <option value="" disabled <?php echo ($edit_categoria === '') ? 'selected' : ''; ?>>Seleccione categoría</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($edit_categoria === $cat) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grupo">
                <label>Cantidad *</label>
                <input type="number" name="cantidad" min="0" placeholder="Ej: 25" value="<?php echo htmlspecialchars($edit_cantidad); ?>" required>
            </div>
        </div>

        <div class="fila">
            <div class="grupo">
                <label>Unidad *</label>
                <input type="text" name="unidad" placeholder="Ej: Tarros, Pares, Rollos" value="<?php echo htmlspecialchars($edit_unidad); ?>" required>
            </div>
            <div class="grupo">
                <label>Proveedor *</label>
                <input type="text" name="proveedor" placeholder="Ej: Distribuidora Calzado S.A." value="<?php echo htmlspecialchars($edit_proveedor); ?>" required>
            </div>
        </div>

        <div class="botones">
            <?php if ($editando): ?>
                <button type="submit" name="editar_material" class="guardar">
                    <i class="fa-solid fa-floppy-disk"></i> Actualizar
                </button>
                <a href="materiales.php" class="cancelar">
                    <i class="fa-solid fa-xmark"></i> Cancelar
                </a>
            <?php else: ?>
                <button type="submit" name="guardar_material" class="guardar">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar
                </button>
                <button type="button" class="cancelar" onclick="toggleFormulario()">
                    <i class="fa-solid fa-xmark"></i> Cancelar
                </button>
            <?php endif; ?>
        </div>
    </form>
</section>

<!-------------------- TARJETAS -------------------->
<section class="cards">
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
<section class="busqueda">
    <div class="buscar">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="buscar" placeholder="Buscar material...">
    </div>
    <select id="filtroCategoria">
        <option value="todas">Todas las categorías</option>
        <?php foreach ($categorias as $cat): ?>
            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
        <?php endforeach; ?>
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
        <th>Proveedor</th>
        <th>Estado</th>
        <th>Acciones</th>
    </tr>
</thead>
<tbody id="tablaMateriales">
    <?php
    if ($resultado_materiales && mysqli_num_rows($resultado_materiales) > 0):
        while ($row = mysqli_fetch_assoc($resultado_materiales)):
            // CORREGIDO: la columna en la BD se llama "Id" (mayúscula). Se acepta "Id" o "id".
            $id_val = $row['Id'] ?? $row['id'] ?? '';
    ?>
            <tr>
                <td><?php echo htmlspecialchars($row['codigo'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['nombredelmaterial'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['categoria'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['cantidad'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['unidad'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['proveedor'] ?? ''); ?></td>
                <td>
                    <?php if (isset($row['cantidad']) && $row['cantidad'] < 5): ?>
                        <span class="estado bajo">Stock Bajo</span>
                    <?php else: ?>
                        <span class="estado disponible">Disponible</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="materiales.php?editar_id=<?php echo urlencode($id_val); ?>" class="editar" title="Editar">
                        <i class="fa-solid fa-pen"></i>
                    </a>
                    <?php if ($es_admin): ?>
                        <a href="#" onclick="confirmarEliminacion(<?php echo (int) $id_val; ?>); return false;" class="eliminar" title="Eliminar">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    <?php endif; ?>
                </td>
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

<!-------------------- PROVEEDORES + ÚLTIMOS MOVIMIENTOS -------------------->
<section class="paneles-inferiores">
    <div class="caja">
        <h2>Proveedores de Materiales</h2>
        <table class="tabla-proveedores">
            <thead>
                <tr>
                    <th>Proveedor Disponible</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado_proveedores && mysqli_num_rows($resultado_proveedores) > 0): ?>
                    <?php while ($prov = mysqli_fetch_assoc($resultado_proveedores)): ?>
                        <tr>
                            <td><i class="fa-solid fa-truck"></i><?php echo htmlspecialchars($prov['proveedor']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td style="text-align:center;">No hay proveedores registrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="caja">
        <h2>Últimos Movimientos de Materiales</h2>
        <ul class="movimientos">
            <?php if ($resultado_historial && mysqli_num_rows($resultado_historial) > 0): ?>
                <?php while ($mov = mysqli_fetch_assoc($resultado_historial)):
                    $tipo = $mov['tipo'] ?? '';
                    if ($tipo === 'agregado') {
                        $icono = 'fa-circle-plus';
                        $etiqueta = 'Agregado:';
                    } elseif ($tipo === 'editado') {
                        $icono = 'fa-pen-to-square';
                        $etiqueta = 'Editado:';
                    } else {
                        $icono = 'fa-trash-can';
                        $etiqueta = 'Eliminado:';
                    }
                ?>
                    <li>
                        <i class="fa-solid <?php echo $icono; ?>"></i>
                        <span><strong><?php echo $etiqueta; ?></strong> <?php echo htmlspecialchars($mov['detalle'] ?? ''); ?></span>
                    </li>
                <?php endwhile; ?>
            <?php else: ?>
                <li>Aún no hay movimientos registrados.</li>
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
            window.location.href = 'materiales.php?eliminar_material=' + encodeURIComponent(id);
        }
    });
}

document.addEventListener("DOMContentLoaded", () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('editar_id')) {
        const form = document.querySelector('.formulario');
        if (form) {
            form.scrollIntoView({ behavior: 'smooth' });
        }
    }

    const inputBuscar = document.getElementById("buscar");
    const selectCategoria = document.getElementById("filtroCategoria");
    const filas = document.querySelectorAll("#tablaMateriales tr");

    function filtrarTabla() {
        const textoBusqueda = inputBuscar ? inputBuscar.value.toLowerCase() : "";
        const categoriaSeleccionada = selectCategoria ? selectCategoria.value.toLowerCase() : "todas";

        filas.forEach(fila => {
            const celdas = fila.getElementsByTagName("td");
            if (celdas.length < 3) return;

            const textoFila = fila.textContent.toLowerCase();
            const categoriaFila = celdas[2].textContent.toLowerCase().trim();

            const coincideTexto = textoFila.includes(textoBusqueda);
            const coincideCategoria = (categoriaSeleccionada === "todas") || (categoriaFila === categoriaSeleccionada);

            fila.style.display = (coincideTexto && coincideCategoria) ? "" : "none";
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