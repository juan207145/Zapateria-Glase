<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../conexion.php';
require_once __DIR__ . '/componentes/actividad.php';

// Control de acceso
if (!isset($_SESSION['Usuario'])) {
    header("Location: ../index.php");
    exit();
}

$usuario = $_SESSION['Usuario'];
$rol = $_SESSION['Rol'] ?? '';
$es_admin = (strtolower($rol) === 'administrador');
$rol_mostrar = $es_admin ? 'Administrador' : 'Empleado';

// ---------- Utilidades ----------
function h($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

// Ejecuta una consulta y devuelve todas las filas (si falla devuelve [] y no rompe la página)
function filas($conexion, $sql) {
    try {
        $res = $conexion->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    } catch (Throwable $e) {
        return [];
    }
}

function valor($conexion, $sql) {
    $f = filas($conexion, $sql);
    if (!$f) {
        return 0;
    }
    return reset($f[0]) ?? 0;
}

function fecha_corta($f) {
    if (!$f) {
        return '—';
    }
    $t = strtotime($f);
    return $t ? date('d/m/Y', $t) : '—';
}

// ---------- 2. TARJETAS ----------
$arreglos_activos = (int) valor($conexion, "SELECT COUNT(*) FROM arreglos WHERE estado IN ('Pendiente','En Proceso')");
$productos_stock  = (int) valor($conexion, "SELECT COALESCE(SUM(cantidad),0) FROM productos");
$total_clientes   = (int) valor($conexion, "SELECT COUNT(*) FROM clientes");
$valor_stock      = (float) valor($conexion, "SELECT COALESCE(SUM(cantidad * precio),0) FROM productos");

// ---------- 3. ÚLTIMOS PROVEEDORES Y ACTIVIDAD RECIENTE ----------
$proveedores = filas($conexion, "SELECT p.nombre AS proveedor, p.nit, COUNT(m.id) AS num_materiales, COALESCE(SUM(m.cantidad), 0) AS unidades
    FROM proveedores p LEFT JOIN materiales m ON m.proveedor_id = p.id
    GROUP BY p.id, p.nombre, p.nit
    ORDER BY p.id DESC LIMIT 5");
$actividad   = filas($conexion, "SELECT modulo, detalle, tipo, fecha FROM historial_actividad ORDER BY id DESC LIMIT 6");

// ---------- 4. ÚLTIMOS 4 ARREGLOS ----------
$ultimos_arreglos = filas($conexion, "SELECT a.id, c.nombres, a.tipo_arreglo, a.fecha_ingreso, a.fecha_entrega, a.estado
    FROM arreglos a LEFT JOIN clientes c ON c.id = a.cliente_id
    ORDER BY a.id DESC LIMIT 4");

// ---------- 5. PRODUCTOS AGOTADOS O POR AGOTARSE ----------
$inventario = filas($conexion, "SELECT nombre, cantidad FROM productos WHERE cantidad < 5 ORDER BY cantidad ASC, nombre ASC LIMIT 8");

// ---------- 6. CLIENTES RECIENTES Y ENTREGAS DE HOY ----------
$clientes_recientes = filas($conexion, "SELECT nombres, telefono, localidad FROM clientes ORDER BY id DESC LIMIT 4");
// Entregas de hoy y de los próximos 4 días (sin contar los arreglos ya finalizados)
$entregas_hoy = filas($conexion, "SELECT c.nombres, a.tipo_arreglo, a.estado, a.fecha_entrega
    FROM arreglos a LEFT JOIN clientes c ON c.id = a.cliente_id
    WHERE a.fecha_entrega BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 4 DAY)
      AND a.estado <> 'Finalizado'
    ORDER BY a.fecha_entrega ASC, a.id ASC");


// ---------- ALERTAS DEL SISTEMA ----------
// Cada alerta: [nivel (critica|aviso), icono, texto, grupo]
$alertas = [];

$n_prod_agotados = (int) valor($conexion, "SELECT COUNT(*) FROM productos WHERE cantidad <= 0");
$n_prod_bajos    = (int) valor($conexion, "SELECT COUNT(*) FROM productos WHERE cantidad > 0 AND cantidad < 5");
$n_mat_agotados  = (int) valor($conexion, "SELECT COUNT(*) FROM materiales WHERE cantidad <= 0");
$n_mat_bajos     = (int) valor($conexion, "SELECT COUNT(*) FROM materiales WHERE cantidad > 0 AND cantidad < stock_minimo");

foreach (filas($conexion, "SELECT nombre FROM productos WHERE cantidad <= 0 ORDER BY nombre LIMIT 10") as $f) {
    $alertas[] = ['critica', 'fa-circle-xmark', 'Se acabó el stock del producto ' . $f['nombre'] . '.', 'prod_agotados'];
}
foreach (filas($conexion, "SELECT nombre, cantidad FROM productos WHERE cantidad > 0 AND cantidad < 5 ORDER BY cantidad ASC LIMIT 10") as $f) {
    $alertas[] = ['aviso', 'fa-triangle-exclamation', 'Stock bajo del producto ' . $f['nombre'] . ' (' . (int) $f['cantidad'] . ' unidades).', 'prod_bajos'];
}
foreach (filas($conexion, "SELECT nombredelmaterial AS nombre FROM materiales WHERE cantidad <= 0 ORDER BY nombredelmaterial LIMIT 10") as $f) {
    $alertas[] = ['critica', 'fa-circle-xmark', 'Se acabó el material ' . $f['nombre'] . '.', 'mat_agotados'];
}
foreach (filas($conexion, "SELECT nombredelmaterial AS nombre, cantidad FROM materiales WHERE cantidad > 0 AND cantidad < stock_minimo ORDER BY cantidad ASC LIMIT 10") as $f) {
    $alertas[] = ['aviso', 'fa-triangle-exclamation', 'Stock bajo del material ' . $f['nombre'] . ' (' . (int) $f['cantidad'] . ' disponibles).', 'mat_bajos'];
}

// Arreglos atrasados o que se entregan hoy, mañana o pasado mañana
$n_arr_atrasados = 0;
$n_arr_cercanos  = 0;
$arreglos_alerta = filas($conexion, "SELECT c.nombres, a.tipo_arreglo, a.fecha_entrega, DATEDIFF(a.fecha_entrega, CURDATE()) AS dias
    FROM arreglos a LEFT JOIN clientes c ON c.id = a.cliente_id
    WHERE a.estado <> 'Finalizado' AND a.fecha_entrega <= DATE_ADD(CURDATE(), INTERVAL 2 DAY)
    ORDER BY a.fecha_entrega ASC LIMIT 10");
foreach ($arreglos_alerta as $f) {
    $dias = (int) $f['dias'];
    $quien = ($f['nombres'] ?? 'Sin cliente') . ' (' . $f['tipo_arreglo'] . ')';
    if ($dias < 0) {
        $n_arr_atrasados++;
        $alertas[] = ['critica', 'fa-clock', 'Arreglo atrasado de ' . $quien . ': debía entregarse el ' . fecha_corta($f['fecha_entrega']) . '.', 'arr_atrasados'];
    } else {
        $n_arr_cercanos++;
        $cuando = ($dias === 0) ? 'hoy' : (($dias === 1) ? 'mañana' : 'el ' . fecha_corta($f['fecha_entrega']));
        $alertas[] = ['aviso', 'fa-clock', 'Arreglo de ' . $quien . ' se entrega ' . $cuando . '.', 'arr_cercanos'];
    }
}

// Alertas agrupadas por tipo para el panel de notificaciones
$definicion_grupos = [
    'arr_atrasados' => ['Arreglos atrasados',            'fa-clock',                'critica'],
    'arr_cercanos'  => ['Arreglos próximos a entregar',  'fa-calendar-day',         'aviso'],
    'prod_agotados' => ['Productos agotados',            'fa-circle-xmark',         'critica'],
    'prod_bajos'    => ['Productos con stock bajo',      'fa-triangle-exclamation', 'aviso'],
    'mat_agotados'  => ['Materiales agotados',           'fa-circle-xmark',         'critica'],
    'mat_bajos'     => ['Materiales con stock bajo',     'fa-triangle-exclamation', 'aviso'],
];
$grupos_alertas = [];
foreach ($definicion_grupos as $clave => $def) {
    $items = [];
    foreach ($alertas as $al) {
        if ($al[3] === $clave) $items[] = $al[2];
    }
    if ($items) {
        $grupos_alertas[] = ['titulo' => $def[0], 'icono' => $def[1], 'nivel' => $def[2], 'items' => $items];
    }
}

// Resumen para la ventana emergente (una línea por tipo de alerta)
$resumen = [];
if ($n_prod_agotados) $resumen[] = $n_prod_agotados . ' producto(s) agotado(s)';
if ($n_prod_bajos)    $resumen[] = $n_prod_bajos . ' producto(s) con stock bajo';
if ($n_mat_agotados)  $resumen[] = $n_mat_agotados . ' material(es) agotado(s)';
if ($n_mat_bajos)     $resumen[] = $n_mat_bajos . ' material(es) con stock bajo';
if ($n_arr_atrasados) $resumen[] = $n_arr_atrasados . ' arreglo(s) atrasado(s)';
if ($n_arr_cercanos)  $resumen[] = $n_arr_cercanos . ' arreglo(s) por entregar en 2 días';

// La ventana emergente sale una vez por inicio de sesión
$mostrar_popup = !empty($resumen) && empty($_SESSION['alertas_mostradas']);
if ($mostrar_popup) {
    $_SESSION['alertas_mostradas'] = true;
}

function clase_estado_arreglo($estado) {
    if ($estado === 'Finalizado') return 'listo';
    if ($estado === 'En Proceso') return 'proceso';
    return 'pendiente';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <title>GLASÉ | Panel Principal</title>

    <link rel="stylesheet" href="../css/dashboard.css?v=<?php echo @filemtime(__DIR__ . '/../css/dashboard.css') ?: time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

<aside class="sidebar">
    <div class="logo" onclick="window.location.href='dashboard.php'" style="cursor:pointer;">
        <i class="fa-solid fa-shoe-prints"></i>
        <h2>GLASÉ</h2>
    </div>

    <ul>
        <li class="active"><a href="dashboard.php"><i class="fa-solid fa-house"></i> Panel Principal</a></li>
        <li><a href="componentes/arreglos.php"><i class="fa-solid fa-screwdriver-wrench"></i> Arreglos</a></li>
        <li><a href="componentes/materiales.php"><i class="fa-solid fa-boxes-stacked"></i> Materiales</a></li>
        <?php if (strtolower($_SESSION['Rol'] ?? '') === 'administrador'): ?>
        <li><a href="componentes/proveedores.php"><i class="fa-solid fa-truck"></i> Proveedores</a></li>
        <?php endif; ?>
        <li><a href="componentes/mercancia.php"><i class="fa-solid fa-box-open"></i> Mercancía</a></li>
        <li><a href="componentes/clientes.php"><i class="fa-solid fa-users"></i> Clientes</a></li>
        <?php if (strtolower($_SESSION['Rol'] ?? '') === 'administrador'): ?>
        <li><a href="componentes/configuracion.php"><i class="fa-solid fa-gear"></i> Configuración</a></li>
        <?php endif; ?>
        <li><a href="../manual/Manual_Usuario_GLASE.pdf" target="_blank" rel="noopener"><i class="fa-solid fa-file-pdf"></i> Manual de Usuario</a></li>
        <li><a href="cerrarsesion.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a></li>
    </ul>
</aside>

<main>

<header>
    <div>
        <h1>Panel Principal</h1>
        <p>Bienvenido al sistema de gestión de la Zapatería GLASÉ.</p>
    </div>

    <div class="usuario" title="<?php echo h($usuario); ?>">
        <i class="fa-solid fa-user-circle"></i>
        <span><?php echo h($rol_mostrar); ?></span>
    </div>

    <div class="reloj" id="reloj"></div>
</header>

<!-------------------- TARJETAS -------------------->
<section class="cards">
    <div class="card">
        <i class="fa-solid fa-screwdriver-wrench"></i>
        <div>
            <h2><?php echo $arreglos_activos; ?></h2>
            <p>Arreglos Activos</p>
        </div>
    </div>

    <div class="card">
        <i class="fa-solid fa-box-open"></i>
        <div>
            <h2><?php echo number_format($productos_stock, 0, ',', '.'); ?></h2>
            <p>Productos en Stock</p>
        </div>
    </div>

    <div class="card">
        <i class="fa-solid fa-users"></i>
        <div>
            <h2><?php echo $total_clientes; ?></h2>
            <p>Clientes</p>
        </div>
    </div>

    <div class="card">
        <i class="fa-solid fa-dollar-sign"></i>
        <div>
            <h2>$<?php echo number_format($valor_stock, 0, ',', '.'); ?></h2>
            <p>Valor del Stock</p>
        </div>
    </div>
</section>

<!-------------------- PROVEEDORES + ACTIVIDAD -------------------->
<section class="dashboard-grid">

<div class="panel actividad">
    <div class="titulo">
        <h2>Últimos Proveedores Registrados</h2>
    </div>
    <ul>
        <?php if ($proveedores): ?>
            <?php foreach ($proveedores as $p):
                $nit = trim((string) ($p['nit'] ?? ''));
                $nit = ($nit === '' || $nit === '0') ? 'Sin NIT' : 'NIT ' . $nit;
            ?>
                <li>
                    <i class="fa-solid fa-truck"></i>
                    <span><?php echo h($p['proveedor']); ?>
                    <span class="detalle-secundario">· <?php echo h($nit); ?></span></span>
                    <span class="cantidad-prov" title="Materiales de este proveedor y unidades en stock">
                        <?php echo (int) $p['num_materiales']; ?> material(es) · <?php echo number_format((int) $p['unidades'], 0, ',', '.'); ?> unid.
                    </span>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li class="vacio">Aún no hay proveedores registrados.</li>
        <?php endif; ?>
    </ul>
</div>

<div class="panel actividad">
    <div class="titulo">
        <h2>Actividad Reciente</h2>
    </div>
    <ul>
        <?php if ($actividad): ?>
            <?php foreach ($actividad as $a):
                if ($a['tipo'] === 'agregado') {
                    $icono = 'fa-circle-plus';
                } elseif ($a['tipo'] === 'editado') {
                    $icono = 'fa-pen-to-square';
                } else {
                    $icono = 'fa-trash-can';
                }
                $t = strtotime($a['fecha']);
            ?>
                <li>
                    <i class="fa-solid <?php echo $icono; ?> tipo-<?php echo h($a['tipo']); ?>"></i>
                    <span>
                        <strong><?php echo h($a['modulo']); ?>:</strong>
                        <?php echo h(preg_replace('/^(Agregado|Editado|Eliminado):\s*/u', '', trim(strip_tags($a['detalle'])))); ?>
                        <span class="detalle-secundario"><?php echo $t ? date('d/m H:i', $t) : ''; ?></span>
                    </span>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li class="vacio">Aún no hay actividad registrada.</li>
        <?php endif; ?>
    </ul>
</div>

</section>

<!-------------------- ÚLTIMOS ARREGLOS -------------------->
<section class="panel">
    <div class="titulo">
        <h2>Últimos Arreglos Registrados</h2>
    </div>

    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Trabajo</th>
                <th>Ingreso</th>
                <th>Entrega</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($ultimos_arreglos): ?>
                <?php foreach ($ultimos_arreglos as $r): ?>
                    <tr>
                        <td><?php echo h($r['nombres'] ?? 'Sin cliente'); ?></td>
                        <td><?php echo h($r['tipo_arreglo']); ?></td>
                        <td><?php echo fecha_corta($r['fecha_ingreso']); ?></td>
                        <td><?php echo fecha_corta($r['fecha_entrega']); ?></td>
                        <td><span class="estado <?php echo clase_estado_arreglo($r['estado']); ?>"><?php echo h($r['estado']); ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="vacio">No hay arreglos registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<!-------------------- INVENTARIO + ALERTAS -------------------->
<section class="dashboard-grid">

<div class="panel">
    <div class="titulo">
        <h2>Productos Agotados o por Agotarse</h2>
    </div>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Disponible</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($inventario): ?>
                <?php foreach ($inventario as $i): $c = (int) $i['cantidad']; ?>
                    <tr>
                        <td><?php echo h($i['nombre']); ?></td>
                        <td><?php echo $c; ?></td>
                        <td>
                            <?php if ($c <= 0): ?>
                                <span class="estado pendiente">Agotado</span>
                            <?php else: ?>
                                <span class="estado proceso">Stock Bajo</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3" class="vacio">Todos los productos tienen stock suficiente.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="panel" id="panel-alertas">
    <div class="titulo">
        <h2>Alertas del Sistema</h2>
    </div>
    <ul class="alertas">
        <?php if ($alertas): ?>
            <?php foreach (array_slice($alertas, 0, 10) as $al): ?>
                <li class="<?php echo h($al[0]); ?>">
                    <i class="fa-solid <?php echo h($al[1]); ?>"></i>
                    <?php echo h($al[2]); ?>
                </li>
            <?php endforeach; ?>
            <?php if (count($alertas) > 10): ?>
                <li class="vacio">y <?php echo count($alertas) - 10; ?> alerta(s) más…</li>
            <?php endif; ?>
        <?php else: ?>
            <li class="ok"><i class="fa-solid fa-circle-check"></i> Todo en orden: no hay alertas por ahora.</li>
        <?php endif; ?>
    </ul>
</div>

</section>

<!-------------------- CLIENTES RECIENTES -------------------->
<section class="panel">
    <div class="titulo">
        <h2>Clientes Recientes</h2>
    </div>

    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Teléfono</th>
                <th>Localidad</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($clientes_recientes): ?>
                <?php foreach ($clientes_recientes as $c): ?>
                    <tr>
                        <td><?php echo h($c['nombres']); ?></td>
                        <td><?php echo h($c['telefono']); ?></td>
                        <td><?php echo h($c['localidad']); ?></td>
                        <td>Activo</td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" class="vacio">No hay clientes registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<!-------------------- ENTREGAS DE HOY -------------------->
<section class="panel">
    <div class="titulo">
        <h2>Próximas Entregas (hoy y los siguientes 4 días)</h2>
    </div>

    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Trabajo</th>
                <th>Entrega</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($entregas_hoy): ?>
                <?php foreach ($entregas_hoy as $e): ?>
                    <tr>
                        <td><?php echo h($e['nombres'] ?? 'Sin cliente'); ?></td>
                        <td><?php echo h($e['tipo_arreglo']); ?></td>
                        <td>
                            <?php echo fecha_corta($e['fecha_entrega']); ?>
                            <?php if ($e['fecha_entrega'] === date('Y-m-d')): ?>
                                <span class="detalle-secundario">(hoy)</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="estado <?php echo clase_estado_arreglo($e['estado']); ?>"><?php echo h($e['estado']); ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" class="vacio">No hay entregas programadas en los próximos 4 días.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<footer>
    <p>© 2026 Sistema GLASÉ | Gestión Integral para Zapaterías</p>
</footer>

</main>

<?php if ($alertas): ?>
<!-- Campana de alertas: globos una por una y panel de notificaciones agrupadas -->
<div class="campana" id="campana">
    <div class="campana-globo" id="campanaGlobo" role="button" tabindex="0">
        <i class="fa-solid fa-triangle-exclamation" id="campanaIcono"></i>
        <span id="campanaTexto"></span>
    </div>
    <button type="button" class="campana-btn" id="campanaBtn" title="Ver notificaciones" aria-label="Ver notificaciones">
        <i class="fa-solid fa-bell"></i>
        <span class="campana-contador"><?php echo count($alertas); ?></span>
    </button>
</div>

<div class="notif-panel" id="notifPanel" role="dialog" aria-label="Notificaciones">
    <div class="notif-cabecera">
        <div>
            <strong><i class="fa-solid fa-bell"></i> Notificaciones</strong>
            <span><?php echo count($alertas); ?> pendiente(s)</span>
        </div>
        <button type="button" class="notif-cerrar" id="notifCerrar" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="notif-cuerpo">
        <?php foreach ($grupos_alertas as $g): ?>
            <div class="notif-grupo <?php echo h($g['nivel']); ?>">
                <button type="button" class="notif-grupo-titulo">
                    <i class="fa-solid <?php echo h($g['icono']); ?>"></i>
                    <span><?php echo h($g['titulo']); ?></span>
                    <em><?php echo count($g['items']); ?></em>
                    <i class="fa-solid fa-chevron-down notif-flecha"></i>
                </button>
                <ul>
                    <?php foreach ($g['items'] as $it): ?>
                        <li><?php echo h($it); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="notif-pie">
        <a href="#panel-alertas" id="notifVerTabla">Ver tabla de alertas</a>
    </div>
</div>

<script>
// Datos de las alertas (los usa js/dashboard.js)
window.GLASE_ALERTAS = {
    lista: <?php echo json_encode(array_map(function ($a) { return ['nivel' => $a[0], 'texto' => $a[2]]; }, $alertas), JSON_UNESCAPED_UNICODE); ?>,
    automatico: <?php echo $mostrar_popup ? 'true' : 'false'; ?>
};
</script>
<?php endif; ?>



<!-- Menú responsivo -->
<button type="button" class="menu-toggle" id="menuToggle" aria-label="Abrir menú"><i class="fa-solid fa-bars"></i></button>
<div class="menu-overlay" id="menuOverlay"></div>
<script src="../js/dashboard.js"></script>
</body>
</html>