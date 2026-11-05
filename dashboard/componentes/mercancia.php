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

// Variables para alertas estilizadas
$alerta_tipo = "";
$alerta_mensaje = "";
$redireccionar = "";

// Categorías permitidas (una sola fuente para el formulario, el filtro y la validación)
$categorias = ['Deportivo', 'Casual', 'Botas', 'Tacones', 'Sandalias'];

// Registra un movimiento en el historial (si la tabla falla, no rompe la página)
function registrar_historial($conexion, $detalle, $tipo) {
    // Para el dashboard: texto limpio, sin etiquetas HTML ni el prefijo "Editado:" repetido
    $detalle_limpio = trim(html_entity_decode(strip_tags($detalle), ENT_QUOTES, 'UTF-8'));
    $detalle_limpio = preg_replace('/^(Agregado|Editado|Eliminado):\s*/u', '', $detalle_limpio);
    registrar_actividad($conexion, 'Mercancía', $detalle_limpio, $tipo);
    $stmt = @$conexion->prepare("INSERT INTO historial_mercancia (detalle, tipo) VALUES (?, ?)");
    if ($stmt) {
        $stmt->bind_param("ss", $detalle, $tipo);
        @$stmt->execute();
        $stmt->close();
    }
}

// Valida los datos de un producto. Devuelve el mensaje de error, o '' si todo está bien.
function validar_producto($codigo, $nombre, $categoria, $talla, $cantidad, $precio, $categorias) {
    if ($codigo === '' || $nombre === '' || $categoria === '' || $talla === '' || $cantidad === '' || $precio === '') {
        return "Por favor completa todos los campos obligatorios.";
    }

    // Código: letras, números y guiones
    if (!preg_match('/^[A-Za-z0-9\-]{2,20}$/', $codigo)) {
        return "El código solo puede tener letras, números y guiones (entre 2 y 20 caracteres).";
    }

    // Nombre: debe tener letras (no solo números) y solo caracteres normales
    if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 60
        || !preg_match('/\p{L}/u', $nombre)
        || !preg_match('/^[\p{L}0-9 .,\'&\-]+$/u', $nombre)) {
        return "El nombre del producto debe tener letras (no solo números) y solo puede incluir letras, números, espacios y . , ' & -";
    }

    // Categoría: una de la lista
    if (!in_array($categoria, $categorias, true)) {
        return "La categoría seleccionada no es válida.";
    }

    // Talla: número entre 15 y 50 (admite media talla: 38.5)
    if (!preg_match('/^\d{2}(\.5)?$/', $talla) || (float) $talla < 15 || (float) $talla > 50) {
        return "La talla debe ser un número entre 15 y 50 (por ejemplo 38 o 38.5).";
    }

    // Cantidad: entero de 0 en adelante (sin negativos, decimales ni letras)
    if (!ctype_digit($cantidad) || (int) $cantidad > 100000) {
        return "La cantidad debe ser un número entero igual o mayor que 0.";
    }

    // Precio: entero mayor que 0 (sin negativos, decimales ni letras)
    if (!ctype_digit($precio) || (int) $precio < 1 || (int) $precio > 100000000) {
        return "El precio debe ser un número entero mayor que 0.";
    }

    return '';
}

// ¿Ya existe otro producto con ese código? ($id_excluir = 0 al crear)
function codigo_repetido($conexion, $codigo, $id_excluir = 0) {
    $stmt = $conexion->prepare("SELECT id FROM productos WHERE codigo = ? AND id <> ? LIMIT 1");
    $stmt->bind_param("si", $codigo, $id_excluir);
    $stmt->execute();
    $existe = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (bool) $existe;
}

// ---------------- 1. ELIMINAR PRODUCTO ----------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['eliminar_producto'])) {
    $id = (int) $_GET['eliminar_producto'];

    if ($es_admin) {
        // Obtener el nombre del producto antes de eliminarlo para el historial
        $consulta_prod = $conexion->prepare("SELECT nombre FROM productos WHERE id = ?");
        $consulta_prod->bind_param("i", $id);
        $consulta_prod->execute();
        $res_prod = $consulta_prod->get_result();
        $nombre_eliminado = "Producto ID " . $id;
        if ($p_dat = $res_prod->fetch_assoc()) {
            $nombre_eliminado = $p_dat['nombre'];
        }
        $consulta_prod->close();

        $stmt = $conexion->prepare("DELETE FROM productos WHERE id=?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            registrar_historial($conexion, "<strong>Eliminado:</strong> Se retiró del inventario " . htmlspecialchars($nombre_eliminado) . ".", 'eliminado');
            $alerta_tipo = "success";
            $alerta_mensaje = "¡Producto eliminado correctamente!";
        } else {
            $alerta_tipo = "error";
            $alerta_mensaje = "Error al eliminar producto: " . $conexion->error;
        }
        $stmt->close();
        $redireccionar = "mercancia.php";
    } else {
        $alerta_tipo = "warning";
        $alerta_mensaje = "Necesitas permisos de administrador para realizar esta acción.";
        $redireccionar = "mercancia.php";
    }
}

// ---------------- 2. PROCESAR GUARDADO DE NUEVO PRODUCTO ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_producto'])) {
    $codigo = trim($_POST['codigo'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $talla = trim($_POST['talla'] ?? '');
    $cantidad = trim($_POST['cantidad'] ?? '');
    $precio = trim($_POST['precio'] ?? '');

    $error = validar_producto($codigo, $nombre, $categoria, $talla, $cantidad, $precio, $categorias);
    if ($error === '' && codigo_repetido($conexion, $codigo)) {
        $error = "Ya existe un producto con el código " . $codigo . ".";
    }

    if ($error === '') {
        $cantidad = (int) $cantidad;
        $precio = (int) $precio;

        $stmt = $conexion->prepare("INSERT INTO productos (codigo, nombre, categoria, talla, cantidad, precio) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssii", $codigo, $nombre, $categoria, $talla, $cantidad, $precio);

        if ($stmt->execute()) {
            registrar_historial($conexion, "<strong>Agregado:</strong> Ingreso de nuevo modelo " . htmlspecialchars($nombre) . ".", 'agregado');
            $alerta_tipo = "success";
            $alerta_mensaje = "¡Producto registrado correctamente!";
        } else {
            $alerta_tipo = "error";
            $alerta_mensaje = "Error al registrar producto: " . $conexion->error;
        }
        $stmt->close();
        $redireccionar = "mercancia.php";
    } else {
        $alerta_tipo = "warning";
        $alerta_mensaje = $error;
    }
}

// ---------------- 3. PROCESAR ACTUALIZACIÓN DE PRODUCTO ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_producto'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $codigo = trim($_POST['codigo'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $talla = trim($_POST['talla'] ?? '');
    $cantidad = trim($_POST['cantidad'] ?? '');
    $precio = trim($_POST['precio'] ?? '');

    $error = validar_producto($codigo, $nombre, $categoria, $talla, $cantidad, $precio, $categorias);
    if ($id <= 0) {
        $error = "Por favor completa todos los campos obligatorios.";
    }
    if ($error === '' && codigo_repetido($conexion, $codigo, $id)) {
        $error = "Ya existe otro producto con el código " . $codigo . ".";
    }

    if ($error === '') {
        $cantidad = (int) $cantidad;
        $precio = (int) $precio;

        $stmt = $conexion->prepare("UPDATE productos SET codigo=?, nombre=?, categoria=?, talla=?, cantidad=?, precio=? WHERE id=?");
        $stmt->bind_param("ssssiii", $codigo, $nombre, $categoria, $talla, $cantidad, $precio, $id);

        if ($stmt->execute()) {
            registrar_historial($conexion, "<strong>Editado:</strong> Modificación en " . htmlspecialchars($nombre) . ".", 'editado');
            $alerta_tipo = "success";
            $alerta_mensaje = "¡Producto actualizado correctamente!";
        } else {
            $alerta_tipo = "error";
            $alerta_mensaje = "Error al actualizar producto: " . $conexion->error;
        }
        $stmt->close();
        $redireccionar = "mercancia.php";
    } else {
        $alerta_tipo = "warning";
        $alerta_mensaje = $error;
    }
}

// ---------------- 4. CARGAR DATOS PARA EDICIÓN ----------------
$editando = false;
$edit_id = $edit_codigo = $edit_nombre = $edit_categoria = $edit_talla = $edit_cantidad = $edit_precio = "";

if (isset($_GET['editar_id'])) {
    $id_editar = (int) $_GET['editar_id'];
    $stmt = $conexion->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($producto_edit = $res->fetch_assoc()) {
        $editando = true;
        $edit_id = $producto_edit['id'];
        $edit_codigo = $producto_edit['codigo'];
        $edit_nombre = $producto_edit['nombre'];
        $edit_categoria = $producto_edit['categoria'];
        $edit_talla = $producto_edit['talla'];
        $edit_cantidad = $producto_edit['cantidad'];
        // El precio se muestra como entero (sin ceros decimales)
        $edit_precio = (string) (int) $producto_edit['precio'];
    }
    $stmt->close();
}

// "Agregar" pulsado mientras se editaba: la página se recarga limpia con el formulario de nuevo registro abierto
$abrir_nuevo = isset($_GET['nuevo']) && !$editando;

// ---------------- 5. CONSULTAS DE ESTADÍSTICAS ----------------
$totalproductos = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM productos");
$productosregistrados = mysqli_fetch_assoc($totalproductos)['total'] ?? 0;

$precioTotal = mysqli_query($conexion, "SELECT SUM(precio * cantidad) AS total_valor FROM productos");
$valorInventario = mysqli_fetch_assoc($precioTotal)['total_valor'] ?? 0;

// Por agotarse: quedan entre 1 y 4. Agotados: quedan 0.
$productosporagotarse = mysqli_query($conexion, "SELECT COUNT(*) AS por_agotarse FROM productos WHERE cantidad > 0 AND cantidad < 5");
$productosagotarse = mysqli_fetch_assoc($productosporagotarse)['por_agotarse'] ?? 0;

$productosagotados_q = mysqli_query($conexion, "SELECT COUNT(*) AS agotados FROM productos WHERE cantidad <= 0");
$productosagotados = mysqli_fetch_assoc($productosagotados_q)['agotados'] ?? 0;

$resultado_productos = mysqli_query($conexion, "SELECT * FROM productos ORDER BY id DESC");
$resultado_categorias = mysqli_query($conexion, "SELECT DISTINCT categoria FROM productos");
$resultado_historial = mysqli_query($conexion, "SELECT * FROM historial_mercancia ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GLASÉ | Mercancía</title>
    <link rel="stylesheet" href="/Zapateria-Glase/css/mercancia.css?v=<?php echo @filemtime($_SERVER['DOCUMENT_ROOT'] . '/Zapateria-Glase/css/mercancia.css') ?: time(); ?>">
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
    <div class="logo" onclick="window.location.href='../dashboard.php'" style="cursor:pointer;">
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
        <li>
            <a href="materiales.php">
                <i class="fa-solid fa-boxes-stacked"></i>
                Materiales
            </a>
        </li>
        <?php if (strtolower($_SESSION['Rol'] ?? '') === 'administrador'): ?>
        <li>
            <a href="proveedores.php">
                <i class="fa-solid fa-truck"></i>
                Proveedores
            </a>
        </li>
        <?php endif; ?>
        <li class="active">
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
        <?php if (strtolower($_SESSION['Rol'] ?? '') === 'administrador'): ?>
        <li>
            <a href="configuracion.php">
                <i class="fa-solid fa-gear"></i>
                Configuración
            </a>
        </li>
        <?php endif; ?>
        <li>
            <a href="../cerrarsesion.php">
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
        <h1>Mercancía</h1>
        <p>Administración de los productos disponibles para la venta.</p>
    </div>
    <button class="btnAgregar" onclick="agregarNuevo()">
        <i class="fa-solid fa-plus"></i>
        Agregar Producto
    </button>
</header>

<!-------------------- FORMULARIO (CREAR O EDITAR) -------------------->
<section class="formulario <?php echo ($editando || $abrir_nuevo) ? '' : 'formulario-oculto'; ?>">
    <h2><?php echo $editando ? 'Editar Producto' : 'Registrar Producto'; ?></h2>
    <form action="mercancia.php" method="POST" id="formProducto">
        <?php if ($editando): ?>
            <input type="hidden" name="id" value="<?php echo (int) $edit_id; ?>">
        <?php endif; ?>

        <div class="fila">
            <div class="grupo">
                <label>Código *</label>
                <input type="text" name="codigo" id="codigo" maxlength="20" pattern="[A-Za-z0-9\-]{2,20}"
                       title="Solo letras, números y guiones (2 a 20 caracteres)"
                       placeholder="Ej: PR001" value="<?php echo htmlspecialchars($edit_codigo); ?>" required>
            </div>
            <div class="grupo">
                <label>Nombre del Producto *</label>
                <input type="text" name="nombre" id="nombre" maxlength="60"
                       pattern="(?=.*[A-Za-zÁÉÍÓÚÜÑáéíóúüñ])[A-Za-zÁÉÍÓÚÜÑáéíóúüñ0-9 .,'&amp;\-]{3,60}"
                       title="Debe tener letras (no solo números). Se permiten letras, números, espacios y . , ' &amp; -"
                       placeholder="Ej: Tenis Blancos" value="<?php echo htmlspecialchars($edit_nombre); ?>" required>
            </div>
        </div>

        <div class="fila">
            <div class="grupo">
                <label>Categoría *</label>
                <select name="categoria" required>
                    <option value="" disabled <?php echo in_array($edit_categoria, $categorias, true) ? '' : 'selected'; ?>>Seleccione categoría</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($edit_categoria === $cat) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grupo">
                <label>Talla *</label>
                <input type="text" name="talla" id="talla" inputmode="decimal" maxlength="4" pattern="[0-9]{2}(\.5)?"
                       title="Número entre 15 y 50 (por ejemplo 38 o 38.5)"
                       placeholder="Ej: 38" value="<?php echo htmlspecialchars($edit_talla); ?>" required>
            </div>
        </div>

        <div class="fila">
            <div class="grupo">
                <label>Cantidad *</label>
                <input type="text" name="cantidad" id="cantidad" inputmode="numeric" maxlength="6" pattern="[0-9]{1,6}"
                       title="Solo números enteros, 0 o más"
                       placeholder="Ej: 15" value="<?php echo htmlspecialchars($edit_cantidad); ?>" required>
            </div>
            <div class="grupo">
                <label>Precio *</label>
                <input type="text" name="precio" id="precio" inputmode="numeric" maxlength="9" pattern="[1-9][0-9]{0,8}"
                       title="Solo números enteros mayores que 0"
                       placeholder="Ej: 180000" value="<?php echo htmlspecialchars($edit_precio); ?>" required>
            </div>
        </div>

        <div class="botones">
            <?php if ($editando): ?>
                <button type="submit" name="editar_producto" class="guardar">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Actualizar
                </button>
                <a href="mercancia.php" class="cancelar">
                    <i class="fa-solid fa-xmark"></i>
                    Cancelar
                </a>
            <?php else: ?>
                <button type="submit" name="guardar_producto" class="guardar">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Guardar
                </button>
                <button type="button" class="cancelar" onclick="cancelarFormulario()">
                    <i class="fa-solid fa-xmark"></i>
                    Cancelar
                </button>
            <?php endif; ?>
        </div>
    </form>
</section>

<!-------------------- TARJETAS -------------------->
<section class="cards">
    <div class="card">
        <i class="fa-solid fa-box-open"></i>
        <div>
            <h2><?php echo $productosregistrados; ?></h2>
            <p>Productos Registrados</p>
        </div>
    </div>
    <div class="card">
        <i class="fa-solid fa-dollar-sign"></i>
        <div>
            <h2>$<?php echo number_format($valorInventario, 0, ',', '.'); ?></h2>
            <p>Valor del Inventario</p>
        </div>
    </div>
    <div class="card">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div>
            <h2><?php echo $productosagotarse; ?></h2>
            <p>Productos por Agotarse</p>
        </div>
    </div>
    <div class="card">
        <i class="fa-solid fa-ban"></i>
        <div>
            <h2><?php echo $productosagotados; ?></h2>
            <p>Productos Agotados</p>
        </div>
    </div>
</section>

<!-------------------- BUSCADOR Y FILTRO DE CATEGORÍAS -------------------->
<section class="busqueda">
    <div class="buscar">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="buscar" placeholder="Buscar producto...">
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
    <h2>Listado de Productos</h2>
</div>
<table>
<thead>
    <tr>
        <th>Código</th>
        <th>Producto</th>
        <th>Categoría</th>
        <th>Talla</th>
        <th>Cantidad</th>
        <th>Precio</th>
        <th>Estado</th>
        <th>Acciones</th>
    </tr>
</thead>
<tbody id="tablaProductos">
    <?php if ($resultado_productos && mysqli_num_rows($resultado_productos) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($resultado_productos)): ?>
            <?php $cant_fila = (int) $row['cantidad']; ?>
            <tr>
                <td><?php echo htmlspecialchars($row['codigo']); ?></td>
                <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                <td><?php echo htmlspecialchars($row['categoria']); ?></td>
                <td><?php echo htmlspecialchars($row['talla']); ?></td>
                <td><?php echo htmlspecialchars($row['cantidad']); ?></td>
                <td>$<?php echo number_format($row['precio'], 0, ',', '.'); ?></td>
                <td>
                    <?php if ($cant_fila <= 0): ?>
                        <span class="estado agotado">Agotado</span>
                    <?php elseif ($cant_fila < 5): ?>
                        <span class="estado bajo">Stock Bajo</span>
                    <?php else: ?>
                        <span class="estado disponible">Disponible</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="mercancia.php?editar_id=<?php echo (int) $row['id']; ?>" class="editar" title="Editar">
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
            <td colspan="8" class="celda-vacia">No hay productos registrados en el inventario.</td>
        </tr>
    <?php endif; ?>
</tbody>
</table>
</section>

<!-------------------- PANEL INFERIOR -------------------->
<section class="dashboard-grid">
<div class="panel">
<div class="titulo">
    <h2>Categorías de Calzado</h2>
</div>
<table>
<thead>
    <tr>
        <th>Categoría Disponible</th>
    </tr>
</thead>
<tbody>
    <?php if ($resultado_categorias && mysqli_num_rows($resultado_categorias) > 0): ?>
        <?php while($cat = mysqli_fetch_assoc($resultado_categorias)): ?>
            <tr><td><i class="fa-solid fa-tag icono-categoria"></i> <?php echo htmlspecialchars($cat['categoria']); ?></td></tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td>No hay categorías registradas aún.</td></tr>
    <?php endif; ?>
</tbody>
</table>
</div>

<div class="panel">
<div class="titulo">
    <h2>Últimos Movimientos de Productos</h2>
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
        <li><i class="fa-solid fa-circle-plus"></i> <span>Ingreso reciente en inventario.</span></li>
        <li><i class="fa-solid fa-pen-to-square"></i> <span>Actualización de stock de calzado.</span></li>
        <li><i class="fa-solid fa-trash-can"></i> <span>Sin eliminaciones recientes.</span></li>
    <?php endif; ?>
</ul>
</div>
</section>

<!-------------------- RESUMEN -------------------->
<section class="panel">
<div class="titulo">
    <h2>Resumen General</h2>
</div>
<p>
Este módulo permite administrar toda la mercancía disponible para la venta dentro
de la zapatería GLASÉ. Desde aquí es posible registrar nuevos productos,
consultar existencias, controlar las cantidades disponibles, conocer el valor
del inventario y realizar un seguimiento del estado de cada artículo.
</p>
</section>

<!-------------------- FOOTER -------------------->
<footer>
<p>© 2026 Sistema GLASÉ | Gestión Integral para Zapaterías</p>
</footer>
</main>

<script>
const editando = <?php echo $editando ? 'true' : 'false'; ?>;
const abrirNuevo = <?php echo $abrir_nuevo ? 'true' : 'false'; ?>;

// Botón "Agregar Producto": si se estaba editando, recarga la página limpia en modo "nuevo"
function agregarNuevo() {
    if (editando) {
        window.location.href = 'mercancia.php?nuevo=1';
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
        // Al ocultar el formulario de "nuevo producto" se borra lo escrito
        document.getElementById('formProducto').reset();
    }
}

// Cancelar un registro nuevo: limpia todos los campos y oculta el formulario
function cancelarFormulario() {
    document.getElementById('formProducto').reset();
    document.querySelector('.formulario').classList.add('formulario-oculto');
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
            window.location.href = 'mercancia.php?eliminar_producto=' + encodeURIComponent(id);
        }
    });
}

document.addEventListener("DOMContentLoaded", () => {
    if (abrirNuevo) {
        document.querySelector('.formulario').scrollIntoView({ behavior: 'smooth' });
    }

    // ----- Restricciones de los campos del formulario -----
    // Quita lo que no esté permitido mientras se escribe (también al pegar)
    function limitar(idCampo, regexProhibido) {
        const campo = document.getElementById(idCampo);
        campo.addEventListener('input', () => {
            const limpio = campo.value.replace(regexProhibido, '');
            if (campo.value !== limpio) {
                campo.value = limpio;
            }
        });
    }

    limitar('codigo', /[^A-Za-z0-9\-]/g);                  // letras, números y guiones
    limitar('nombre', /[^\p{L}0-9 .,'&\-]/gu);             // sin símbolos raros
    limitar('talla', /[^0-9.]/g);                          // número (38 o 38.5)
    limitar('cantidad', /\D/g);                            // solo dígitos: sin negativos, "e" ni decimales
    limitar('precio', /\D/g);                              // solo dígitos: sin negativos, "e" ni decimales

    // ----- Buscador y filtro de categoría combinado en tiempo real -----
    const inputBuscar = document.getElementById("buscar");
    const selectCategoria = document.getElementById("filtroCategoria");
    const filas = document.querySelectorAll("#tablaProductos tr");

    function filtrarTabla() {
        const textoBusqueda = inputBuscar ? inputBuscar.value.toLowerCase() : "";
        const categoriaSeleccionada = selectCategoria ? selectCategoria.value.toLowerCase() : "todas";

        filas.forEach(fila => {
            const celdas = fila.getElementsByTagName("td");
            // Si la fila no tiene celdas suficientes (ej: fila de "No hay productos"), omitir
            if (celdas.length < 3) return;

            const textoFila = fila.textContent.toLowerCase();
            const categoriaFila = celdas[2].textContent.toLowerCase().trim(); // La 3ra columna es 'Categoría'

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

<!-- Menú responsivo -->
<button type="button" class="menu-toggle" id="menuToggle" aria-label="Abrir menú"><i class="fa-solid fa-bars"></i></button>
<div class="menu-overlay" id="menuOverlay"></div>
<script src="../../js/mercancia.js"></script>
</body>
</html>