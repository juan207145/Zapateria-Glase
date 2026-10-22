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


// ---------------- 1. ELIMINAR PRODUCTO ----------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['eliminar_producto'])) {
    $id = $_GET['eliminar_producto'];

    if ($rol === 'Administrador' || $rol === 'administrador') {
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
            // Registrar la eliminación en el historial
            $accion_reciente = "<strong>Eliminado:</strong> Se retiró del inventario " . htmlspecialchars($nombre_eliminado) . ".";
            @mysqli_query($conexion, "INSERT INTO historial_mercancia (detalle, tipo) VALUES ('$accion_reciente', 'eliminado')");

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
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $categoria = trim($_POST['categoria']);
    $talla = trim($_POST['talla']);
    $cantidad = trim($_POST['cantidad']);
    $precio = trim($_POST['precio']);

    if (!empty($nombre) && !empty($codigo) && !empty($categoria) && !empty($talla) && !empty($cantidad) && !empty($precio)) {
        $stmt = $conexion->prepare("INSERT INTO productos (codigo, nombre, categoria, talla, cantidad, precio) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssii", $codigo, $nombre, $categoria, $talla, $cantidad, $precio);
        
        if ($stmt->execute()) {
            $accion_reciente = "<strong>Agregado:</strong> Ingreso de nuevo modelo " . htmlspecialchars($nombre) . ".";
            @mysqli_query($conexion, "INSERT INTO historial_mercancia (detalle, tipo) VALUES ('$accion_reciente', 'agregado')");

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
        $alerta_mensaje = "Por favor completa todos los campos obligatorios.";
    }
}

// ---------------- 3. PROCESAR ACTUALIZACIÓN DE PRODUCTO ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_producto'])) {
    $id = $_POST['id'];
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $categoria = trim($_POST['categoria']);
    $talla = trim($_POST['talla']);
    $cantidad = trim($_POST['cantidad']);
    $precio = trim($_POST['precio']);
   
    if (!empty($nombre) && !empty($codigo) && !empty($categoria) && !empty($talla) && !empty($cantidad) && !empty($precio)) {
        $stmt = $conexion->prepare("UPDATE productos SET codigo=?, nombre=?, categoria=?, talla=?, cantidad=?, precio=? WHERE id=?");
        $stmt->bind_param("ssssiii", $codigo, $nombre, $categoria, $talla, $cantidad, $precio, $id);

        if ($stmt->execute()) {
            $accion_reciente = "<strong>Editado:</strong> Modificación en " . htmlspecialchars($nombre) . ".";
            @mysqli_query($conexion, "INSERT INTO historial_mercancia (detalle, tipo) VALUES ('$accion_reciente', 'editado')");

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
        $alerta_mensaje = "Por favor completa todos los campos obligatorios.";
    }
} 

// ---------------- 4. CARGAR DATOS PARA EDICIÓN ----------------
$editando = false;
$edit_id = $edit_codigo = $edit_nombre = $edit_categoria = $edit_talla = $edit_cantidad = $edit_precio = "";

if (isset($_GET['editar_id'])) {
    $id_editar = $_GET['editar_id'];
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
        $edit_precio = $producto_edit['precio'];
    }
    $stmt->close();
}

// ---------------- 5. CONSULTAS DE ESTADÍSTICAS ----------------
$totalproductos = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM productos");
$productosregistrados = mysqli_fetch_assoc($totalproductos)['total'] ?? 0;

$precioTotal = mysqli_query($conexion, "SELECT SUM(precio * cantidad) AS total_valor FROM productos");
$valorInventario = mysqli_fetch_assoc($precioTotal)['total_valor'] ?? 0;

$productosporagotarse = mysqli_query($conexion, "SELECT COUNT(*) AS por_agotarse FROM productos WHERE cantidad < 5");
$productosagotarse = mysqli_fetch_assoc($productosporagotarse)['por_agotarse'] ?? 0;

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
    <link rel="stylesheet" href="/Zapateria-Glase/css/mercancia.css">
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
        <li>
            <a href="materiales.php">
                <i class="fa-solid fa-boxes-stacked"></i>
                Materiales
            </a>
        </li>
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

<!-------------------- CONTENIDO -------------------->
<main>
<header>
    <div>
        <h1>Mercancía</h1>
        <p>Administración de los productos disponibles para la venta.</p>
    </div>
    <button class="btnAgregar" onclick="toggleFormulario()">
        <i class="fa-solid fa-plus"></i>
        Agregar Producto
    </button>
</header>

<!-------------------- FORMULARIO (CREAR O EDITAR) -------------------->
<section class="formulario <?php echo $editando ? '' : 'formulario-oculto'; ?>">
    <h2><?php echo $editando ? 'Editar Producto' : 'Registrar Producto'; ?></h2>
    <form action="mercancia.php" method="POST">
        <?php if ($editando): ?>
            <input type="hidden" name="id" value="<?php echo $edit_id; ?>">
        <?php endif; ?>

        <div class="fila">
            <div class="grupo">
                <label>Código *</label>
                <input type="text" name="codigo" placeholder="Ej: PR001" value="<?php echo htmlspecialchars($edit_codigo); ?>" required>
            </div>
            <div class="grupo">
                <label>Nombre del Producto *</label>
                <input type="text" name="nombre" placeholder="Ej: Tenis Blancos" value="<?php echo htmlspecialchars($edit_nombre); ?>" required>
            </div>
        </div>

        <div class="fila">
            <div class="grupo">
                <label>Categoría *</label>
                <select name="categoria" required>
                    <option value="" disabled selected>Seleccione categoría</option>
                    <option value="Deportivo" <?php if($edit_categoria=='Deportivo') echo 'selected'; ?>>Deportivo</option>
                    <option value="Casual" <?php if($edit_categoria=='Casual') echo 'selected'; ?>>Casual</option>
                    <option value="Botas" <?php if($edit_categoria=='Botas') echo 'selected'; ?>>Botas</option>
                    <option value="Tacones" <?php if($edit_categoria=='Tacones') echo 'selected'; ?>>Tacones</option>
                    <option value="Sandalias" <?php if($edit_categoria=='Sandalias') echo 'selected'; ?>>Sandalias</option>
                </select>
            </div>
            <div class="grupo">
                <label>Talla *</label>
                <input type="text" name="talla" placeholder="Ej: 38" value="<?php echo htmlspecialchars($edit_talla); ?>" required>
            </div>
        </div>

        <div class="fila">
            <div class="grupo">
                <label>Cantidad *</label>
                <input type="number" name="cantidad" placeholder="Ej: 15" value="<?php echo htmlspecialchars($edit_cantidad); ?>" required>
            </div>
            <div class="grupo">
                <label>Precio *</label>
                <input type="number" step="0.01" name="precio" placeholder="Ej: 180000" value="<?php echo htmlspecialchars($edit_precio); ?>" required>
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
                <button type="button" class="cancelar" onclick="toggleFormulario()">
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
</section>

<!-------------------- BUSCADOR Y FILTRO DE CATEGORÍAS -------------------->
<section class="busqueda">
    <div class="buscar">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="buscar" placeholder="Buscar producto...">
    </div>
    <select id="filtroCategoria">
        <option value="todas">Todas las categorías</option>
        <option value="Deportivo">Deportivo</option>
        <option value="Casual">Casual</option>
        <option value="Botas">Botas</option>
        <option value="Tacones">Tacones</option>
        <option value="Sandalias">Sandalias</option>
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
            <tr>
                <td><?php echo htmlspecialchars($row['codigo']); ?></td>
                <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                <td><?php echo htmlspecialchars($row['categoria']); ?></td>
                <td><?php echo htmlspecialchars($row['talla']); ?></td>
                <td><?php echo htmlspecialchars($row['cantidad']); ?></td>
                <td>$<?php echo number_format($row['precio'], 0, ',', '.'); ?></td>
                <td>
                    <?php if ($row['cantidad'] < 5): ?>
                        <span class="estado bajo">Stock Bajo</span>
                    <?php else: ?>
                        <span class="estado disponible">Disponible</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="mercancia.php?editar_id=<?php echo $row['id']; ?>" class="editar" title="Editar">
                        <i class="fa-solid fa-pen"></i>
                    </a>
                    <?php if ($rol === 'Administrador' || $rol === 'administrador'): ?>
                        <a href="#" onclick="confirmarEliminacion(<?php echo $row['id']; ?>)" class="eliminar" title="Eliminar">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="8" style="text-align: center;">No hay productos registrados en el inventario.</td>
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
            <tr><td><i class="fa-solid fa-tag" style="margin-right:8px; color:#6D4C41;"></i> <?php echo htmlspecialchars($cat['categoria']); ?></td></tr>
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
            window.location.href = 'mercancia.php?eliminar_producto=' + id;
        }
    })
}

// BUSCADOR Y FILTRO DE CATEGORÍA COMBINADO EN TIEMPO REAL
document.addEventListener("DOMContentLoaded", () => {
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