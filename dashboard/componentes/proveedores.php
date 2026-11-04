<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../../conexion.php';
require_once __DIR__ . '/actividad.php';

// Control de acceso a la sesión
if (!isset($_SESSION['Usuario'])) {
    header("Location: ../../index.php");
    exit();
}

$usuario = $_SESSION['Usuario'];
$rol = $_SESSION['Rol'] ?? '';
$es_admin = (strtolower($rol) === 'administrador');

// Módulo exclusivo del Administrador
if (!$es_admin) {
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
    echo "<body style='background:#F5F3F0;'></body>";
    echo "<script>
        window.addEventListener('DOMContentLoaded', () => {
            Swal.fire({
                icon: 'error',
                title: 'Acceso Denegado',
                text: 'Solo el Administrador puede acceder al módulo de proveedores.',
                confirmButtonColor: '#6D4C41',
                confirmButtonText: 'Volver al Inicio'
            }).then(() => {
                window.location.href = '../dashboard.php';
            });
        });
    </script>";
    exit();
}

// Variables para alertas estilizadas
$alerta_tipo = "";
$alerta_mensaje = "";
$redireccionar = "";

// Categorías de productos que puede suministrar un proveedor (materiales y mercancía)
$categorias = ['Suelas', 'Pegantes', 'Hilos', 'Plantillas', 'Accesorios', 'Deportivo', 'Casual', 'Botas', 'Tacones', 'Sandalias'];

// Registra un movimiento en el historial de actividad (nunca rompe la página)
function registrar_historial($conexion, $detalle, $tipo) {
    registrar_actividad($conexion, 'Proveedores', $detalle, $tipo);
}

// Valida los datos de un proveedor. Devuelve el mensaje de error, o '' si todo está bien.
function validar_proveedor($conexion, $id_excluir, $nit, $nombre, $telefono, $correo, $ciudad, $direccion, $categoria, $categorias) {
    if ($nit === '' || $nombre === '' || $ciudad === '' || $categoria === '') {
        return "Por favor completa NIT, nombre, ciudad y categoría.";
    }
    if (!ctype_digit($nit) || strlen($nit) < 5 || strlen($nit) > 15) {
        return "El NIT debe contener solo números (entre 5 y 15 dígitos).";
    }
    if (preg_match('/\d/', $nombre) || mb_strlen($nombre) < 3 || mb_strlen($nombre) > 150) {
        return "El nombre del proveedor no puede contener números y debe tener al menos 3 letras.";
    }
    if ($telefono !== '' && (!ctype_digit($telefono) || strlen($telefono) < 7 || strlen($telefono) > 15)) {
        return "El teléfono debe contener solo números (entre 7 y 15 dígitos).";
    }
    if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        return "El correo electrónico no es válido.";
    }
    if (preg_match('/\d/', $ciudad) || mb_strlen($ciudad) < 3) {
        return "La ciudad no puede contener números y debe tener al menos 3 letras.";
    }
    if (mb_strlen($direccion) > 200) {
        return "La dirección es demasiado larga (máximo 200 caracteres).";
    }
    if (!in_array($categoria, $categorias, true)) {
        return "Selecciona una categoría válida.";
    }

    // NIT único
    $stmt = $conexion->prepare("SELECT id FROM proveedores WHERE nit = ? AND id <> ? LIMIT 1");
    $stmt->bind_param("si", $nit, $id_excluir);
    $stmt->execute();
    $repetido = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    if ($repetido) {
        return "Ya existe un proveedor registrado con ese NIT.";
    }
    return '';
}

// ---------------- 1. ELIMINAR PROVEEDOR ----------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['eliminar_proveedor'])) {
    $id = (int) $_GET['eliminar_proveedor'];
    $redireccionar = "proveedores.php";

    if (!$es_admin) {
        $alerta_tipo = "warning";
        $alerta_mensaje = "Necesitas permisos de administrador.";
    } else {
        // No se elimina si tiene materiales asociados
        $stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM materiales WHERE proveedor_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $asociados = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();

        if ($asociados > 0) {
            $alerta_tipo = "warning";
            $alerta_mensaje = "No se puede eliminar: tiene " . $asociados . " material(es) asociado(s). Cámbialos de proveedor primero.";
        } else {
            $nombre_eliminado = "ID " . $id;
            $stmtN = $conexion->prepare("SELECT nombre FROM proveedores WHERE id = ?");
            $stmtN->bind_param("i", $id);
            $stmtN->execute();
            if ($fila = $stmtN->get_result()->fetch_assoc()) {
                $nombre_eliminado = $fila['nombre'];
            }
            $stmtN->close();

            $stmt = $conexion->prepare("DELETE FROM proveedores WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                registrar_historial($conexion, "Se eliminó el proveedor " . $nombre_eliminado . ".", 'eliminado');
                $alerta_tipo = "success";
                $alerta_mensaje = "¡Proveedor eliminado correctamente!";
            } else {
                $alerta_tipo = "error";
                $alerta_mensaje = "Error al eliminar: " . $conexion->error;
            }
            $stmt->close();
        }
    }
}

// ---------------- 2. GUARDAR NUEVO PROVEEDOR ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_proveedor'])) {
    $nit = trim($_POST['nit'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');

    $error = validar_proveedor($conexion, 0, $nit, $nombre, $telefono, $correo, $ciudad, $direccion, $categoria, $categorias);

    if ($error === '') {
        $stmt = $conexion->prepare("INSERT INTO proveedores (nit, nombre, telefono, correo, direccion, ciudad, categoria) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $nit, $nombre, $telefono, $correo, $direccion, $ciudad, $categoria);
        if ($stmt->execute()) {
            registrar_historial($conexion, "Nuevo proveedor registrado: " . $nombre . ".", 'agregado');
            $alerta_tipo = "success";
            $alerta_mensaje = "¡Proveedor registrado correctamente!";
        } else {
            $alerta_tipo = "error";
            $alerta_mensaje = "Error SQL al guardar: " . $conexion->error;
        }
        $stmt->close();
        $redireccionar = "proveedores.php";
    } else {
        $alerta_tipo = "warning";
        $alerta_mensaje = $error;
    }
}

// ---------------- 3. ACTUALIZAR PROVEEDOR ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_proveedor'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $nit = trim($_POST['nit'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');

    $error = validar_proveedor($conexion, $id, $nit, $nombre, $telefono, $correo, $ciudad, $direccion, $categoria, $categorias);
    if ($id <= 0) {
        $error = "No se encontró el proveedor a editar.";
    }

    if ($error === '') {
        $conexion->begin_transaction();
        try {
            $stmt = $conexion->prepare("UPDATE proveedores SET nit=?, nombre=?, telefono=?, correo=?, direccion=?, ciudad=?, categoria=? WHERE id=?");
            $stmt->bind_param("sssssssi", $nit, $nombre, $telefono, $correo, $direccion, $ciudad, $categoria, $id);
            $stmt->execute();
            $stmt->close();

            // Los materiales conservan una copia del nombre y NIT: se mantienen al día
            $stmtM = $conexion->prepare("UPDATE materiales SET proveedor = ?, nit = ? WHERE proveedor_id = ?");
            $stmtM->bind_param("ssi", $nombre, $nit, $id);
            $stmtM->execute();
            $stmtM->close();

            $conexion->commit();
            registrar_historial($conexion, "Se modificó el proveedor " . $nombre . ".", 'editado');
            $alerta_tipo = "success";
            $alerta_mensaje = "¡Proveedor actualizado correctamente!";
            $redireccionar = "proveedores.php";
        } catch (Throwable $e) {
            $conexion->rollback();
            $alerta_tipo = "error";
            $alerta_mensaje = "Error SQL al actualizar: " . $e->getMessage();
        }
    } else {
        $alerta_tipo = "warning";
        $alerta_mensaje = $error;
    }
}

// ---------------- 4. CARGAR DATOS PARA EDICIÓN ----------------
$editando = false;
$e = ['id' => '', 'nit' => '', 'nombre' => '', 'telefono' => '', 'correo' => '', 'ciudad' => '', 'direccion' => '', 'categoria' => ''];

if (isset($_GET['editar_id'])) {
    $id_editar = (int) $_GET['editar_id'];
    $stmt = $conexion->prepare("SELECT * FROM proveedores WHERE id = ?");
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    if ($fila = $stmt->get_result()->fetch_assoc()) {
        $editando = true;
        foreach ($e as $clave => $_) {
            $e[$clave] = (string) ($fila[$clave] ?? '');
        }
    }
    $stmt->close();
}

// "Agregar" pulsado mientras se editaba: la página se recarga limpia con el formulario de nuevo registro abierto
$abrir_nuevo = isset($_GET['nuevo']) && !$editando;

// ---------------- 5. CONSULTAS ----------------
$total_proveedores = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS t FROM proveedores"))['t'] ?? 0;
$total_ciudades = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(DISTINCT ciudad) AS t FROM proveedores"))['t'] ?? 0;
$total_enlazados = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS t FROM materiales WHERE proveedor_id IS NOT NULL"))['t'] ?? 0;

$resultado = mysqli_query($conexion, "SELECT p.*, (SELECT COUNT(*) FROM materiales m WHERE m.proveedor_id = p.id) AS num_materiales
    FROM proveedores p ORDER BY p.id DESC");
$resultado_ciudades = mysqli_query($conexion, "SELECT DISTINCT ciudad FROM proveedores ORDER BY ciudad ASC");
$resultado_historial = mysqli_query($conexion, "SELECT detalle, tipo FROM historial_actividad WHERE modulo = 'Proveedores' ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GLASÉ | Proveedores</title>
    <link rel="stylesheet" href="/Zapateria-Glase/css/materiales.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        <li><a href="materiales.php"><i class="fa-solid fa-boxes-stacked"></i> Materiales</a></li>
        <li class="active"><a href="proveedores.php"><i class="fa-solid fa-truck"></i> Proveedores</a></li>
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
        <h1>Proveedores</h1>
        <p>Consulta y administración de los proveedores de materiales y mercancía.</p>
    </div>
    <button class="btnAgregar" onclick="agregarNuevo()">
        <i class="fa-solid fa-plus"></i> Agregar Proveedor
    </button>
</header>

<!-------------------- FORMULARIO (CREAR O EDITAR) -------------------->
<section class="formulario <?php echo ($editando || $abrir_nuevo) ? '' : 'formulario-oculto'; ?>">
    <h2><?php echo $editando ? 'Editar Proveedor' : 'Registrar Proveedor'; ?></h2>
    <form action="proveedores.php" method="POST" id="formProveedor">
        <?php if ($editando): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($e['id']); ?>">
        <?php endif; ?>

        <div class="fila">
            <div class="grupo">
                <label>NIT *</label>
                <input type="text" name="nit" id="nit" inputmode="numeric" pattern="[0-9]{5,15}" maxlength="15"
                       title="Solo números (entre 5 y 15 dígitos)" placeholder="Ej: 900123456"
                       value="<?php echo htmlspecialchars($e['nit']); ?>" required>
            </div>
            <div class="grupo">
                <label>Nombre del Proveedor *</label>
                <input type="text" name="nombre" id="nombre" pattern="[^0-9]{3,}" minlength="3" maxlength="150"
                       title="El nombre no puede contener números" placeholder="Ej: Distribuidora Calzado S.A."
                       value="<?php echo htmlspecialchars($e['nombre']); ?>" required>
            </div>
        </div>

        <div class="fila">
            <div class="grupo">
                <label>Teléfono</label>
                <input type="text" name="telefono" id="telefono" inputmode="numeric" pattern="[0-9]{7,15}" maxlength="15"
                       title="Solo números (entre 7 y 15 dígitos)" placeholder="Ej: 3001234567"
                       value="<?php echo htmlspecialchars($e['telefono']); ?>">
            </div>
            <div class="grupo">
                <label>Correo electrónico</label>
                <input type="email" name="correo" maxlength="150" placeholder="Ej: ventas@proveedor.com"
                       value="<?php echo htmlspecialchars($e['correo']); ?>">
            </div>
        </div>

        <div class="fila">
            <div class="grupo">
                <label>Ciudad *</label>
                <input type="text" name="ciudad" id="ciudad" pattern="[^0-9]{3,}" minlength="3" maxlength="80"
                       title="La ciudad no puede contener números" placeholder="Ej: Bogotá"
                       value="<?php echo htmlspecialchars($e['ciudad']); ?>" required>
            </div>
            <div class="grupo">
                <label>Dirección</label>
                <input type="text" name="direccion" maxlength="200" placeholder="Ej: Calle 10 # 5-20"
                       value="<?php echo htmlspecialchars($e['direccion']); ?>">
            </div>
        </div>

        <div class="fila">
            <div class="grupo">
                <label>Categoría de productos que suministra *</label>
                <select name="categoria" required>
                    <option value="" disabled <?php echo in_array($e['categoria'], $categorias, true) ? '' : 'selected'; ?>>Seleccione categoría</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($e['categoria'] === $cat) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="botones">
            <?php if ($editando): ?>
                <button type="submit" name="editar_proveedor" class="guardar">
                    <i class="fa-solid fa-floppy-disk"></i> Actualizar
                </button>
                <a href="proveedores.php" class="cancelar">
                    <i class="fa-solid fa-xmark"></i> Cancelar
                </a>
            <?php else: ?>
                <button type="submit" name="guardar_proveedor" class="guardar">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar
                </button>
                <button type="button" class="cancelar" onclick="cancelarFormulario()">
                    <i class="fa-solid fa-xmark"></i> Cancelar
                </button>
            <?php endif; ?>
        </div>
    </form>
</section>

<!-------------------- TARJETAS -------------------->
<section class="cards">
    <div class="card">
        <i class="fa-solid fa-truck"></i>
        <div>
            <h2><?php echo $total_proveedores; ?></h2>
            <p>Proveedores Registrados</p>
        </div>
    </div>
    <div class="card">
        <i class="fa-solid fa-location-dot"></i>
        <div>
            <h2><?php echo $total_ciudades; ?></h2>
            <p>Ciudades</p>
        </div>
    </div>
    <div class="card">
        <i class="fa-solid fa-boxes-stacked"></i>
        <div>
            <h2><?php echo $total_enlazados; ?></h2>
            <p>Materiales con Proveedor</p>
        </div>
    </div>
</section>

<!-------------------- BUSCADOR Y FILTROS -------------------->
<section class="busqueda">
    <div class="buscar">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="buscar" placeholder="Buscar por NIT, nombre, teléfono, correo...">
    </div>
    <select id="filtroCiudad">
        <option value="todas">Todas las ciudades</option>
        <?php while ($c = mysqli_fetch_assoc($resultado_ciudades)): ?>
            <option value="<?php echo htmlspecialchars($c['ciudad']); ?>"><?php echo htmlspecialchars($c['ciudad']); ?></option>
        <?php endwhile; ?>
    </select>
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
    <h2>Listado de Proveedores</h2>
</div>
<table>
<thead>
    <tr>
        <th>NIT</th>
        <th>Proveedor</th>
        <th>Contacto</th>
        <th>Ubicación</th>
        <th>Categoría</th>
        <th>Materiales</th>
        <th>Acciones</th>
    </tr>
</thead>
<tbody id="tablaProveedores">
    <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($resultado)): ?>
            <tr data-ciudad="<?php echo htmlspecialchars($row['ciudad']); ?>" data-categoria="<?php echo htmlspecialchars($row['categoria']); ?>">
                <td><?php echo htmlspecialchars($row['nit']); ?></td>
                <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                <td>
                    <?php echo htmlspecialchars($row['telefono'] ?: '—'); ?><br>
                    <small><?php echo htmlspecialchars($row['correo'] ?: '—'); ?></small>
                </td>
                <td>
                    <?php echo htmlspecialchars($row['ciudad']); ?><br>
                    <small><?php echo htmlspecialchars($row['direccion'] ?: '—'); ?></small>
                </td>
                <td><?php echo htmlspecialchars($row['categoria']); ?></td>
                <td><?php echo (int) $row['num_materiales']; ?></td>
                <td>
                    <a href="proveedores.php?editar_id=<?php echo (int) $row['id']; ?>" class="editar" title="Editar">
                        <i class="fa-solid fa-pen"></i>
                    </a>
                    <?php if ($es_admin): ?>
                        <a href="#" onclick="confirmarEliminacion(<?php echo (int) $row['id']; ?>); return false;" class="eliminar" title="Eliminar">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="7" class="celda-vacia">No hay proveedores registrados.</td>
        </tr>
    <?php endif; ?>
</tbody>
</table>
</section>

<!-------------------- ÚLTIMOS MOVIMIENTOS -------------------->
<section class="paneles-inferiores">
    <div class="caja">
        <h2>Últimos Movimientos de Proveedores</h2>
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

<footer>
<p>© 2026 Sistema GLASÉ | Gestión Integral de Inventario</p>
</footer>
</main>

<script>
const editando = <?php echo $editando ? 'true' : 'false'; ?>;

// Botón "Agregar Proveedor": si se estaba editando, recarga la página limpia en modo "nuevo"
function agregarNuevo() {
    if (editando) {
        window.location.href = 'proveedores.php?nuevo=1';
        return;
    }
    toggleFormulario();
}

function toggleFormulario() {
    const form = document.querySelector('.formulario');
    form.classList.toggle('formulario-oculto');
    if (!form.classList.contains('formulario-oculto')) {
        form.scrollIntoView({ behavior: 'smooth' });
    } else if (!editando) {
        document.getElementById('formProveedor').reset();
    }
}

// Cancelar un registro nuevo: limpia todos los campos y oculta el formulario
function cancelarFormulario() {
    document.getElementById('formProveedor').reset();
    document.querySelector('.formulario').classList.add('formulario-oculto');
}

function confirmarEliminacion(id) {
    Swal.fire({
        title: '¿Eliminar proveedor?',
        text: "Esta acción no se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#6D4C41',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'proveedores.php?eliminar_proveedor=' + encodeURIComponent(id);
        }
    });
}

document.addEventListener("DOMContentLoaded", () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('editar_id') || urlParams.has('nuevo')) {
        const form = document.querySelector('.formulario');
        if (form) {
            form.scrollIntoView({ behavior: 'smooth' });
        }
    }

    // Restricciones de los campos
    const soloNumeros = (id) => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', () => { el.value = el.value.replace(/\D/g, ''); });
    };
    const sinNumeros = (id) => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', () => { el.value = el.value.replace(/[0-9]/g, ''); });
    };
    soloNumeros('nit');
    soloNumeros('telefono');
    sinNumeros('nombre');
    sinNumeros('ciudad');

    // Buscador y filtros
    const inputBuscar = document.getElementById("buscar");
    const selectCiudad = document.getElementById("filtroCiudad");
    const selectCategoria = document.getElementById("filtroCategoria");
    const filas = document.querySelectorAll("#tablaProveedores tr");

    function filtrarTabla() {
        const texto = inputBuscar.value.toLowerCase();
        const ciudad = selectCiudad.value.toLowerCase();
        const categoria = selectCategoria.value.toLowerCase();

        filas.forEach(fila => {
            if (!fila.dataset.ciudad) return;
            const coincideTexto = fila.textContent.toLowerCase().includes(texto);
            const coincideCiudad = (ciudad === "todas") || (fila.dataset.ciudad.toLowerCase() === ciudad);
            const coincideCategoria = (categoria === "todas") || (fila.dataset.categoria.toLowerCase() === categoria);
            fila.style.display = (coincideTexto && coincideCiudad && coincideCategoria) ? "" : "none";
        });
    }

    inputBuscar.addEventListener("keyup", filtrarTabla);
    selectCiudad.addEventListener("change", filtrarTabla);
    selectCategoria.addEventListener("change", filtrarTabla);
});
</script>
</body>
</html>