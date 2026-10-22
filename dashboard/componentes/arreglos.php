<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../../conexion.php';

// Hace que los errores de MySQL lancen excepciones (necesario para que las transacciones hagan rollback)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Control de acceso a la sesión
if (!isset($_SESSION['Usuario'])) {
    header("Location: ../../index.php");
    exit();
}

$rol = $_SESSION['Rol'] ?? '';
$es_admin = (strtolower($rol) === 'administrador');

// ---------------- BÚSQUEDA DE CLIENTE POR DOCUMENTO (AJAX) ----------------
if (isset($_GET['buscar_documento'])) {
    header('Content-Type: application/json; charset=utf-8');
    $doc = trim($_GET['buscar_documento']);
    $respuesta = ['encontrado' => false];

    if ($doc !== '') {
        $stmt = $conexion->prepare("SELECT id, nombres, documento, telefono, correo, direccion, ciudad, observaciones FROM clientes WHERE documento = ? LIMIT 1");
        $stmt->bind_param("s", $doc);
        $stmt->execute();
        $c = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($c) {
            $respuesta = ['encontrado' => true, 'cliente' => $c];
        }
    }
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit();
}

$alerta_tipo = "";
$alerta_mensaje = "";
$redireccionar = "";

$estados_validos = ['Pendiente', 'En Proceso', 'Finalizado'];
$tipos_calzado = ['Tenis', 'Botas', 'Zapato Formal', 'Sandalias', 'Tacones'];
$tipos_arreglo = ['Cambio de Suela', 'Costura', 'Pegado', 'Cambio de Tacón', 'Limpieza'];

// ---------------- FUNCIONES AUXILIARES ----------------

// Registra un movimiento en el historial de materiales (nunca rompe la página)
function registrar_historial($conexion, $detalle, $tipo) {
    try {
        $stmt = $conexion->prepare("INSERT INTO historial_materiales (detalle, tipo) VALUES (?, ?)");
        $stmt->bind_param("ss", $detalle, $tipo);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        // Si la tabla de historial falla, se ignora
    }
}

// Lee y consolida los materiales enviados en el formulario: [material_id => cantidad]
function leer_usos() {
    $mat_ids = $_POST['material_id'] ?? [];
    $mat_cant = $_POST['material_cantidad'] ?? [];
    $usos = [];
    foreach ($mat_ids as $i => $mid) {
        $mid = (int) $mid;
        $c = (int) ($mat_cant[$i] ?? 0);
        if ($mid > 0 && $c > 0) {
            $usos[$mid] = ($usos[$mid] ?? 0) + $c;
        }
    }
    return $usos;
}

// Devuelve el id del cliente: el seleccionado o uno nuevo registrado con todos sus datos.
// Debe llamarse dentro de una transacción.
function resolver_cliente($conexion) {
    $modo = $_POST['modo_cliente'] ?? 'existente';

    if ($modo === 'nuevo') {
        $nombres = trim($_POST['nc_nombres'] ?? '');
        $documento = trim($_POST['nc_documento'] ?? '');
        $telefono = trim($_POST['nc_telefono'] ?? '');
        $correo = trim($_POST['nc_correo'] ?? '');
        $direccion = trim($_POST['nc_direccion'] ?? '');
        $ciudad = trim($_POST['nc_ciudad'] ?? '');
        $observaciones = trim($_POST['nc_observaciones'] ?? '');

        if ($nombres === '' || $documento === '' || $telefono === '') {
            throw new Exception("Para registrar el cliente completa Nombres, Documento y Teléfono.");
        }

        // Evitar duplicados por documento
        $stmtD = $conexion->prepare("SELECT nombres FROM clientes WHERE documento = ?");
        $stmtD->bind_param("s", $documento);
        $stmtD->execute();
        $dup = $stmtD->get_result()->fetch_assoc();
        $stmtD->close();
        if ($dup) {
            throw new Exception("Ya existe un cliente con el documento " . $documento . " (" . $dup['nombres'] . "). Selecciónalo de la lista.");
        }

        $stmt = $conexion->prepare("INSERT INTO clientes (nombres, documento, telefono, correo, direccion, ciudad, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $nombres, $documento, $telefono, $correo, $direccion, $ciudad, $observaciones);
        $stmt->execute();
        $nuevo_id = $conexion->insert_id;
        $stmt->close();
        return $nuevo_id;
    }

    $cid = (int) ($_POST['cliente_id'] ?? 0);
    if ($cid <= 0) {
        throw new Exception("Busca al cliente por su documento antes de guardar.");
    }
    $stmtC = $conexion->prepare("SELECT id FROM clientes WHERE id = ?");
    $stmtC->bind_param("i", $cid);
    $stmtC->execute();
    $existe = $stmtC->get_result()->fetch_assoc();
    $stmtC->close();
    if (!$existe) {
        throw new Exception("El cliente seleccionado ya no existe.");
    }
    return $cid;
}

// Nombre de un material (o null si ya no existe)
function nombre_material($conexion, $material_id) {
    $stmt = $conexion->prepare("SELECT nombredelmaterial FROM materiales WHERE id = ?");
    $stmt->bind_param("i", $material_id);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $fila ? $fila['nombredelmaterial'] : null;
}

// Lee y valida los datos generales del arreglo. Devuelve array o null si hay error.
function leer_datos_arreglo($estados_validos) {
    $d = [
        'tipo_calzado' => trim($_POST['tipo_calzado'] ?? ''),
        'tipo_arreglo' => trim($_POST['tipo_arreglo'] ?? ''),
        'fecha_ingreso' => trim($_POST['fecha_ingreso'] ?? ''),
        'fecha_entrega' => trim($_POST['fecha_entrega'] ?? ''),
        'valor' => trim($_POST['valor'] ?? ''),
        'estado' => trim($_POST['estado'] ?? 'Pendiente'),
        'observaciones' => trim($_POST['observaciones'] ?? ''),
    ];
    $ok = ($d['tipo_calzado'] !== '' && $d['tipo_arreglo'] !== '' && $d['fecha_ingreso'] !== ''
        && $d['valor'] !== '' && is_numeric($d['valor']) && in_array($d['estado'], $estados_validos, true));
    if (!$ok) {
        return null;
    }
    $d['valor'] = (float) $d['valor'];
    $d['fecha_entrega'] = ($d['fecha_entrega'] !== '') ? $d['fecha_entrega'] : null;
    return $d;
}

// ---------------- 1. GUARDAR ARREGLO + DESCONTAR MATERIALES ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_arreglo'])) {
    $d = leer_datos_arreglo($estados_validos);
    $usos = leer_usos();

    if ($d === null) {
        $alerta_tipo = "warning";
        $alerta_mensaje = "Por favor completa todos los campos obligatorios.";
    } else {
        $conexion->begin_transaction();
        try {
            $cliente_id = resolver_cliente($conexion);

            $stmt = $conexion->prepare("INSERT INTO arreglos (cliente_id, tipo_calzado, tipo_arreglo, fecha_ingreso, fecha_entrega, valor, estado, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssdss", $cliente_id, $d['tipo_calzado'], $d['tipo_arreglo'], $d['fecha_ingreso'], $d['fecha_entrega'], $d['valor'], $d['estado'], $d['observaciones']);
            $stmt->execute();
            $arreglo_id = $conexion->insert_id;
            $stmt->close();

            $historial = [];
            foreach ($usos as $material_id => $cant) {
                $nombre = nombre_material($conexion, $material_id);
                if ($nombre === null) {
                    throw new Exception("Uno de los materiales seleccionados ya no existe.");
                }

                // Descuento atómico: solo si hay stock suficiente
                $stmtU = $conexion->prepare("UPDATE materiales SET cantidad = cantidad - ? WHERE id = ? AND cantidad >= ?");
                $stmtU->bind_param("iii", $cant, $material_id, $cant);
                $stmtU->execute();
                $afectadas = $stmtU->affected_rows;
                $stmtU->close();
                if ($afectadas !== 1) {
                    throw new Exception("Stock insuficiente de " . $nombre . ".");
                }

                $stmtR = $conexion->prepare("INSERT INTO arreglo_materiales (arreglo_id, material_id, cantidad) VALUES (?, ?, ?)");
                $stmtR->bind_param("iii", $arreglo_id, $material_id, $cant);
                $stmtR->execute();
                $stmtR->close();

                $historial[] = "Uso de " . $cant . " de " . $nombre . " en un arreglo.";
            }

            $conexion->commit();
            foreach ($historial as $h) {
                registrar_historial($conexion, $h, 'editado');
            }

            $alerta_tipo = "success";
            $alerta_mensaje = "¡Arreglo registrado y materiales descontados del stock!";
            $redireccionar = "arreglos.php";
        } catch (Exception $e) {
            $conexion->rollback();
            $alerta_tipo = "error";
            $alerta_mensaje = $e->getMessage();
        }
    }
}

// ---------------- 2. ACTUALIZAR ARREGLO + AJUSTAR STOCK ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_arreglo'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $d = leer_datos_arreglo($estados_validos);
    $usos = leer_usos();

    if ($id <= 0 || $d === null) {
        $alerta_tipo = "warning";
        $alerta_mensaje = "Por favor completa todos los campos obligatorios.";
    } else {
        $conexion->begin_transaction();
        try {
            // Verificar que el arreglo exista
            $stmtV = $conexion->prepare("SELECT id FROM arreglos WHERE id = ?");
            $stmtV->bind_param("i", $id);
            $stmtV->execute();
            $existe = $stmtV->get_result()->fetch_assoc();
            $stmtV->close();
            if (!$existe) {
                throw new Exception("El arreglo ya no existe.");
            }

            $cliente_id = resolver_cliente($conexion);

            // Materiales que tenía antes
            $previos = [];
            $stmtP = $conexion->prepare("SELECT material_id, cantidad FROM arreglo_materiales WHERE arreglo_id = ?");
            $stmtP->bind_param("i", $id);
            $stmtP->execute();
            $resP = $stmtP->get_result();
            while ($p = $resP->fetch_assoc()) {
                $previos[(int) $p['material_id']] = ($previos[(int) $p['material_id']] ?? 0) + (int) $p['cantidad'];
            }
            $stmtP->close();

            // Ajustar el stock según la diferencia entre lo nuevo y lo anterior
            $historial = [];
            $todos = array_unique(array_merge(array_keys($previos), array_keys($usos)));
            foreach ($todos as $material_id) {
                $nuevo = $usos[$material_id] ?? 0;
                $viejo = $previos[$material_id] ?? 0;
                $dif = $nuevo - $viejo;
                if ($dif === 0) {
                    continue;
                }
                $nombre = nombre_material($conexion, $material_id);

                if ($dif > 0) {
                    // Necesita más material: se descuenta la diferencia
                    if ($nombre === null) {
                        throw new Exception("Uno de los materiales seleccionados ya no existe.");
                    }
                    $stmtU = $conexion->prepare("UPDATE materiales SET cantidad = cantidad - ? WHERE id = ? AND cantidad >= ?");
                    $stmtU->bind_param("iii", $dif, $material_id, $dif);
                    $stmtU->execute();
                    $afectadas = $stmtU->affected_rows;
                    $stmtU->close();
                    if ($afectadas !== 1) {
                        throw new Exception("Stock insuficiente de " . $nombre . ".");
                    }
                    $historial[] = "Uso adicional de " . $dif . " de " . $nombre . " (arreglo modificado).";
                } else {
                    // Usa menos material: se devuelve la diferencia al stock
                    $devolver = -$dif;
                    $stmtD = $conexion->prepare("UPDATE materiales SET cantidad = cantidad + ? WHERE id = ?");
                    $stmtD->bind_param("ii", $devolver, $material_id);
                    $stmtD->execute();
                    $stmtD->close();
                    $historial[] = "Se devolvieron " . $devolver . " de " . ($nombre ?? "material") . " al stock (arreglo modificado).";
                }
            }

            // Reemplazar la lista de materiales del arreglo
            $stmtX = $conexion->prepare("DELETE FROM arreglo_materiales WHERE arreglo_id = ?");
            $stmtX->bind_param("i", $id);
            $stmtX->execute();
            $stmtX->close();

            foreach ($usos as $material_id => $cant) {
                $stmtR = $conexion->prepare("INSERT INTO arreglo_materiales (arreglo_id, material_id, cantidad) VALUES (?, ?, ?)");
                $stmtR->bind_param("iii", $id, $material_id, $cant);
                $stmtR->execute();
                $stmtR->close();
            }

            // Datos generales
            $stmt = $conexion->prepare("UPDATE arreglos SET cliente_id=?, tipo_calzado=?, tipo_arreglo=?, fecha_ingreso=?, fecha_entrega=?, valor=?, estado=?, observaciones=? WHERE id=?");
            $stmt->bind_param("issssdssi", $cliente_id, $d['tipo_calzado'], $d['tipo_arreglo'], $d['fecha_ingreso'], $d['fecha_entrega'], $d['valor'], $d['estado'], $d['observaciones'], $id);
            $stmt->execute();
            $stmt->close();

            $conexion->commit();
            foreach ($historial as $h) {
                registrar_historial($conexion, $h, 'editado');
            }

            $alerta_tipo = "success";
            $alerta_mensaje = "¡Arreglo actualizado y stock ajustado correctamente!";
            $redireccionar = "arreglos.php";
        } catch (Exception $e) {
            $conexion->rollback();
            $alerta_tipo = "error";
            $alerta_mensaje = $e->getMessage();
        }
    }
}

// ---------------- 3. CAMBIAR ESTADO ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $estado = trim($_POST['estado'] ?? '');

    if ($id > 0 && in_array($estado, $estados_validos, true)) {
        $stmt = $conexion->prepare("UPDATE arreglos SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $estado, $id);
        $stmt->execute();
        $stmt->close();
        $alerta_tipo = "success";
        $alerta_mensaje = "Estado actualizado correctamente.";
        $redireccionar = "arreglos.php";
    }
}

// ---------------- 4. ELIMINAR ARREGLO (devuelve stock si no estaba finalizado) ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_arreglo'])) {
    $id = (int) ($_POST['id'] ?? 0);

    if (!$es_admin) {
        $alerta_tipo = "warning";
        $alerta_mensaje = "Necesitas permisos de administrador.";
        $redireccionar = "arreglos.php";
    } elseif ($id > 0) {
        $conexion->begin_transaction();
        try {
            $stmtE = $conexion->prepare("SELECT estado FROM arreglos WHERE id = ?");
            $stmtE->bind_param("i", $id);
            $stmtE->execute();
            $arr = $stmtE->get_result()->fetch_assoc();
            $stmtE->close();

            $devuelto = false;
            if ($arr) {
                // Si no se finalizó, los materiales no se consumieron: se devuelven al stock
                if ($arr['estado'] !== 'Finalizado') {
                    $stmtM = $conexion->prepare("SELECT material_id, cantidad FROM arreglo_materiales WHERE arreglo_id = ?");
                    $stmtM->bind_param("i", $id);
                    $stmtM->execute();
                    $resM = $stmtM->get_result();
                    while ($m = $resM->fetch_assoc()) {
                        $stmtD = $conexion->prepare("UPDATE materiales SET cantidad = cantidad + ? WHERE id = ?");
                        $stmtD->bind_param("ii", $m['cantidad'], $m['material_id']);
                        $stmtD->execute();
                        $stmtD->close();
                        $devuelto = true;
                    }
                    $stmtM->close();
                }

                $stmtX = $conexion->prepare("DELETE FROM arreglo_materiales WHERE arreglo_id = ?");
                $stmtX->bind_param("i", $id);
                $stmtX->execute();
                $stmtX->close();

                $stmtY = $conexion->prepare("DELETE FROM arreglos WHERE id = ?");
                $stmtY->bind_param("i", $id);
                $stmtY->execute();
                $stmtY->close();
            }

            $conexion->commit();
            $alerta_tipo = "success";
            $alerta_mensaje = $devuelto
                ? "Arreglo eliminado y materiales devueltos al stock."
                : "Arreglo eliminado correctamente.";
        } catch (Exception $e) {
            $conexion->rollback();
            $alerta_tipo = "error";
            $alerta_mensaje = "Error al eliminar: " . $e->getMessage();
        }
        $redireccionar = "arreglos.php";
    }
}

// ---------------- 5. CARGAR DATOS PARA EDICIÓN ----------------
$editando = false;
$ed = [
    'id' => '', 'cliente_id' => '', 'tipo_calzado' => '', 'tipo_arreglo' => '',
    'fecha_ingreso' => date('Y-m-d'), 'fecha_entrega' => '', 'valor' => '',
    'estado' => 'Pendiente', 'observaciones' => ''
];
$materiales_edit = [];   // filas para rellenar el formulario
$usado_actual = [];      // material_id => cantidad que ya usa este arreglo

if (isset($_GET['editar_id'])) {
    $id_editar = (int) $_GET['editar_id'];
    $stmt = $conexion->prepare("SELECT * FROM arreglos WHERE id = ?");
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($fila) {
        $editando = true;
        foreach ($ed as $k => $v) {
            if (isset($fila[$k])) {
                $ed[$k] = $fila[$k];
            }
        }
        $stmtM = $conexion->prepare("SELECT material_id, cantidad FROM arreglo_materiales WHERE arreglo_id = ?");
        $stmtM->bind_param("i", $id_editar);
        $stmtM->execute();
        $resM = $stmtM->get_result();
        while ($m = $resM->fetch_assoc()) {
            $materiales_edit[] = ['material_id' => (int) $m['material_id'], 'cantidad' => (int) $m['cantidad']];
            $usado_actual[(int) $m['material_id']] = ($usado_actual[(int) $m['material_id']] ?? 0) + (int) $m['cantidad'];
        }
        $stmtM->close();
    }
}

// ---------------- 6. CONSULTAS PARA MOSTRAR ----------------
// Materiales con su stock actual
$lista_materiales = [];
$res_mat = mysqli_query($conexion, "SELECT * FROM materiales ORDER BY nombredelmaterial ASC");
while ($m = mysqli_fetch_assoc($res_mat)) {
    $lista_materiales[] = [
        'id' => (int) ($m['Id'] ?? $m['id'] ?? 0),
        'nombre' => $m['nombredelmaterial'],
        'categoria' => $m['categoria'],
        'cantidad' => (int) $m['cantidad'],
        'unidad' => $m['unidad'],
    ];
}

// Cliente del arreglo en edición (para precargar sus datos)
$cliente_edit = null;
if ($editando && $ed['cliente_id'] !== '') {
    $stmtCE = $conexion->prepare("SELECT id, nombres, documento, telefono, correo, direccion, ciudad, observaciones FROM clientes WHERE id = ?");
    $stmtCE->bind_param("i", $ed['cliente_id']);
    $stmtCE->execute();
    $cliente_edit = $stmtCE->get_result()->fetch_assoc() ?: null;
    $stmtCE->close();
}

// Arreglos con su cliente y los materiales usados
$resultado_arreglos = mysqli_query($conexion, "
    SELECT a.*,
           c.nombres AS cliente_nombre,
           c.telefono AS cliente_telefono,
           (SELECT GROUP_CONCAT(CONCAT(m.nombredelmaterial, ' x', am.cantidad) SEPARATOR ', ')
              FROM arreglo_materiales am
              JOIN materiales m ON m.id = am.material_id
             WHERE am.arreglo_id = a.id) AS materiales_usados
    FROM arreglos a
    LEFT JOIN clientes c ON c.id = a.cliente_id
    ORDER BY a.id DESC
");

function clase_estado($estado) {
    if ($estado === 'Pendiente') return 'pendiente';
    if ($estado === 'En Proceso') return 'proceso';
    return 'finalizado';
}
function sel($a, $b) {
    return ((string) $a === (string) $b) ? 'selected' : '';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GLASÉ | Gestión de Arreglos</title>

    <link rel="stylesheet" href="../../css/arreglos.css">
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

<!------------ SIDEBAR ------------>
<aside class="sidebar">
    <div class="logo">
        <i class="fa-solid fa-shoe-prints"></i>
        <h2>GLASÉ</h2>
    </div>
    <ul>
        <li><a href="../dashboard.php"><i class="fa-solid fa-house"></i> Panel Principal</a></li>
        <li class="active"><a href="arreglos.php"><i class="fa-solid fa-screwdriver-wrench"></i> Arreglos</a></li>
        <li><a href="materiales.php"><i class="fa-solid fa-boxes-stacked"></i> Materiales</a></li>
        <li><a href="mercancia.php"><i class="fa-solid fa-box-open"></i> Mercancía</a></li>
        <li><a href="clientes.php"><i class="fa-solid fa-users"></i> Clientes</a></li>
        <li><a href="configuracion.php"><i class="fa-solid fa-gear"></i> Configuración</a></li>
        <li><a href="../cerrarsesion.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a></li>
    </ul>
</aside>

<!------------ CONTENIDO ------------>
<main>

    <header>
        <div class="titulo">
            <h1>Gestión de Arreglos</h1>
            <p>Registro y seguimiento de reparaciones de calzado.</p>
        </div>
        <button id="nuevo" type="button" onclick="toggleFormulario()">
            <i class="fa-solid fa-plus"></i>
            Nuevo Arreglo
        </button>
    </header>

    <!------------ FORMULARIO (CREAR O EDITAR) ------------>
    <section class="formulario <?php echo $editando ? '' : 'formulario-oculto'; ?>">
        <h2><?php echo $editando ? 'Editar Arreglo' : 'Registrar Arreglo'; ?></h2>

        <form action="arreglos.php" method="POST" id="formArreglo">

            <?php if ($editando): ?>
                <input type="hidden" name="id" value="<?php echo (int) $ed['id']; ?>">
            <?php endif; ?>

            <!-- CLIENTE: se busca por documento y se traen sus datos -->
            <div class="bloque-cliente">
                <h3><i class="fa-solid fa-user"></i> Cliente</h3>
                <input type="hidden" name="modo_cliente" id="modoCliente" value="">
                <input type="hidden" name="cliente_id" id="clienteId" value="">

                <div class="fila-cliente-existente">
                    <div class="grupo">
                        <label>Documento del cliente *</label>
                        <input type="text" name="nc_documento" id="nc_documento" placeholder="Escribe el documento y presiona Buscar" autocomplete="off" required>
                    </div>
                    <button type="button" class="btn-secundario" onclick="buscarCliente()">
                        <i class="fa-solid fa-magnifying-glass"></i> Buscar
                    </button>
                </div>
                <p class="mensaje-cliente" id="mensajeCliente"></p>

                <!-- Datos del cliente: solo lectura si ya existe, editables si es nuevo -->
                <div class="caja-cliente-nuevo" id="datosCliente">
                    <div class="fila">
                        <div class="grupo">
                            <label>Nombres *</label>
                            <input type="text" name="nc_nombres" id="nc_nombres" placeholder="Nombre completo">
                        </div>
                        <div class="grupo">
                            <label>Teléfono *</label>
                            <input type="text" name="nc_telefono" id="nc_telefono" placeholder="Número telefónico">
                        </div>
                    </div>
                    <div class="fila">
                        <div class="grupo">
                            <label>Correo Electrónico</label>
                            <input type="email" name="nc_correo" id="nc_correo" placeholder="Correo">
                        </div>
                        <div class="grupo">
                            <label>Ciudad</label>
                            <input type="text" name="nc_ciudad" id="nc_ciudad" placeholder="Ciudad">
                        </div>
                    </div>
                    <div class="fila">
                        <div class="grupo">
                            <label>Dirección</label>
                            <input type="text" name="nc_direccion" id="nc_direccion" placeholder="Dirección">
                        </div>
                    </div>
                    <div class="grupo">
                        <label>Observaciones del cliente</label>
                        <textarea name="nc_observaciones" id="nc_observaciones" rows="2" placeholder="Información adicional del cliente..."></textarea>
                    </div>
                </div>
            </div>

            <div class="fila">
                <div class="grupo">
                    <label>Tipo de Calzado *</label>
                    <select name="tipo_calzado" required>
                        <option value="" disabled <?php echo ($ed['tipo_calzado'] === '') ? 'selected' : ''; ?>>Seleccione...</option>
                        <?php foreach ($tipos_calzado as $t): ?>
                            <option <?php echo sel($ed['tipo_calzado'], $t); ?>><?php echo $t; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grupo">
                    <label>Tipo de Arreglo *</label>
                    <select name="tipo_arreglo" required>
                        <option value="" disabled <?php echo ($ed['tipo_arreglo'] === '') ? 'selected' : ''; ?>>Seleccione...</option>
                        <?php foreach ($tipos_arreglo as $t): ?>
                            <option <?php echo sel($ed['tipo_arreglo'], $t); ?>><?php echo $t; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="fila">
                <div class="grupo">
                    <label>Fecha de Ingreso *</label>
                    <input type="date" name="fecha_ingreso" value="<?php echo htmlspecialchars($ed['fecha_ingreso']); ?>" required>
                </div>
                <div class="grupo">
                    <label>Fecha de Entrega</label>
                    <input type="date" name="fecha_entrega" value="<?php echo htmlspecialchars($ed['fecha_entrega']); ?>">
                </div>
            </div>

            <div class="fila">
                <div class="grupo">
                    <label>Valor del Servicio *</label>
                    <input type="number" name="valor" min="0" step="any" placeholder="$" value="<?php echo htmlspecialchars($ed['valor']); ?>" required>
                </div>
                <div class="grupo">
                    <label>Estado</label>
                    <select name="estado">
                        <?php foreach ($estados_validos as $est): ?>
                            <option <?php echo sel($ed['estado'], $est); ?>><?php echo $est; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- MATERIALES A USAR -->
            <div class="bloque-materiales">
                <h3><i class="fa-solid fa-boxes-stacked"></i> Materiales a utilizar</h3>
                <div id="listaMateriales"></div>
                <button type="button" class="btn-secundario" onclick="agregarFilaMaterial()">
                    <i class="fa-solid fa-plus"></i> Agregar material
                </button>
                <p class="ayuda-material">
                    <?php echo $editando
                        ? 'Al actualizar, el stock se ajusta según la diferencia con los materiales anteriores.'
                        : 'Las cantidades se descontarán automáticamente del stock al guardar.'; ?>
                </p>
            </div>

            <div class="grupo">
                <label>Observaciones</label>
                <textarea name="observaciones" rows="4" placeholder="Descripción del trabajo realizado..."><?php echo htmlspecialchars($ed['observaciones']); ?></textarea>
            </div>

            <div class="botones">
                <?php if ($editando): ?>
                    <button type="submit" name="actualizar_arreglo" class="guardar">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Actualizar
                    </button>
                    <a href="arreglos.php" class="cancelar">
                        <i class="fa-solid fa-xmark"></i>
                        Cancelar
                    </a>
                <?php else: ?>
                    <button type="submit" name="guardar_arreglo" class="guardar">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Guardar
                    </button>
                    <button type="reset" class="cancelar" onclick="limpiarFormulario()">
                        <i class="fa-solid fa-trash"></i>
                        Limpiar
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <!------------ STOCK DE MATERIALES ------------>
    <section class="panel-stock">
        <h2>Stock actual de materiales</h2>
        <table>
            <thead>
                <tr>
                    <th>Material</th>
                    <th>Categoría</th>
                    <th>Cantidad disponible</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($lista_materiales) > 0): ?>
                    <?php foreach ($lista_materiales as $mat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($mat['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($mat['categoria']); ?></td>
                            <td><?php echo $mat['cantidad'] . ' ' . htmlspecialchars($mat['unidad']); ?></td>
                            <td>
                                <?php if ($mat['cantidad'] < 5): ?>
                                    <span class="tag-stock bajo">Stock Bajo</span>
                                <?php else: ?>
                                    <span class="tag-stock ok">Disponible</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="celda-vacia">No hay materiales registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <!------------ TABLA DE ARREGLOS ------------>
    <section class="tabla">
        <div class="encabezado-tabla">
            <h2>Arreglos Registrados</h2>
            <input type="text" id="buscar" placeholder="Buscar arreglo...">
        </div>

        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Calzado</th>
                    <th>Servicio</th>
                    <th>Materiales usados</th>
                    <th>Entrega</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaArreglos">
                <?php if ($resultado_arreglos && mysqli_num_rows($resultado_arreglos) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($resultado_arreglos)): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($row['cliente_nombre'] ?? 'Cliente no disponible'); ?>
                                <?php if (!empty($row['cliente_telefono'])): ?>
                                    <span class="sub-telefono"><?php echo htmlspecialchars($row['cliente_telefono']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['tipo_calzado']); ?></td>
                            <td><?php echo htmlspecialchars($row['tipo_arreglo']); ?></td>
                            <td class="lista-usados">
                                <?php echo !empty($row['materiales_usados']) ? htmlspecialchars($row['materiales_usados']) : '—'; ?>
                            </td>
                            <td><?php echo !empty($row['fecha_entrega']) ? date('d/m/Y', strtotime($row['fecha_entrega'])) : '—'; ?></td>
                            <td>
                                <span class="<?php echo clase_estado($row['estado']); ?>">
                                    <?php echo htmlspecialchars($row['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <!-- Cambiar estado rápido -->
                                <form action="arreglos.php" method="POST" class="form-inline">
                                    <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                    <input type="hidden" name="cambiar_estado" value="1">
                                    <select name="estado" class="select-estado" onchange="this.form.submit()" title="Cambiar estado">
                                        <?php foreach ($estados_validos as $est): ?>
                                            <option <?php echo sel($row['estado'], $est); ?>><?php echo $est; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>

                                <!-- Editar -->
                                <a href="arreglos.php?editar_id=<?php echo (int) $row['id']; ?>" class="editar" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </a>

                                <?php if ($es_admin): ?>
                                    <button type="button" class="eliminar" title="Eliminar"
                                        onclick="confirmarEliminacion(<?php echo (int) $row['id']; ?>)">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="celda-vacia">No hay arreglos registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <!-- Formulario oculto para eliminar -->
    <form id="formEliminar" action="arreglos.php" method="POST" class="oculto">
        <input type="hidden" name="id" id="idEliminar">
        <input type="hidden" name="eliminar_arreglo" value="1">
    </form>

</main>

<script>
// Datos desde la base de datos
const materiales = <?php echo json_encode($lista_materiales, JSON_UNESCAPED_UNICODE); ?>;
const usadoActual = <?php echo json_encode((object) $usado_actual); ?>;      // lo que ya usa el arreglo en edición
const filasIniciales = <?php echo json_encode($materiales_edit); ?>;          // materiales del arreglo en edición
const editando = <?php echo $editando ? 'true' : 'false'; ?>;
const clienteEditar = <?php echo json_encode($cliente_edit, JSON_UNESCAPED_UNICODE); ?>;   // cliente del arreglo en edición

function toggleFormulario() {
    const form = document.querySelector('.formulario');
    form.classList.toggle('formulario-oculto');
    if (!form.classList.contains('formulario-oculto')) {
        if (document.querySelectorAll('.fila-material').length === 0) {
            agregarFilaMaterial();
        }
        form.scrollIntoView({ behavior: 'smooth' });
    }
}

// ---------- CLIENTE: búsqueda por documento ----------
let documentoBuscado = '';

const camposCliente = {
    nc_nombres: 'nombres',
    nc_telefono: 'telefono',
    nc_correo: 'correo',
    nc_direccion: 'direccion',
    nc_ciudad: 'ciudad',
    nc_observaciones: 'observaciones'
};

function llenarDatosCliente(c, soloLectura) {
    for (const [id, clave] of Object.entries(camposCliente)) {
        const el = document.getElementById(id);
        el.value = c ? (c[clave] || '') : '';
        el.readOnly = soloLectura;
    }
}

function mensajeCliente(texto, clase) {
    const m = document.getElementById('mensajeCliente');
    m.textContent = texto;
    m.className = 'mensaje-cliente' + (clase ? ' ' + clase : '');
}

function resetCliente() {
    document.getElementById('modoCliente').value = '';
    document.getElementById('clienteId').value = '';
    document.getElementById('datosCliente').style.display = 'none';
    document.getElementById('nc_nombres').required = false;
    document.getElementById('nc_telefono').required = false;
    llenarDatosCliente(null, false);
    mensajeCliente('', '');
    documentoBuscado = '';
}

function mostrarClienteExistente(c) {
    document.getElementById('modoCliente').value = 'existente';
    document.getElementById('clienteId').value = c.id;
    llenarDatosCliente(c, true);
    document.getElementById('datosCliente').style.display = 'block';
    document.getElementById('nc_nombres').required = false;
    document.getElementById('nc_telefono').required = false;
    mensajeCliente('Cliente encontrado: sus datos se cargaron automáticamente.', 'ok');
    documentoBuscado = (c.documento || '').trim();
}

function mostrarClienteNuevo(doc) {
    document.getElementById('modoCliente').value = 'nuevo';
    document.getElementById('clienteId').value = '';
    llenarDatosCliente(null, false);
    document.getElementById('datosCliente').style.display = 'block';
    document.getElementById('nc_nombres').required = true;
    document.getElementById('nc_telefono').required = true;
    mensajeCliente('Este documento no está registrado. Completa los datos para registrar al cliente.', 'nuevo');
    documentoBuscado = doc;
    document.getElementById('nc_nombres').focus();
}

async function buscarCliente() {
    const input = document.getElementById('nc_documento');
    const doc = input.value.trim();

    if (doc === '') {
        resetCliente();
        mensajeCliente('Escribe el número de documento del cliente.', 'nuevo');
        return;
    }
    // Evita repetir la búsqueda si ya está resuelta
    if (doc === documentoBuscado && document.getElementById('modoCliente').value !== '') {
        return;
    }

    try {
        const resp = await fetch('arreglos.php?buscar_documento=' + encodeURIComponent(doc), {
            headers: { 'Accept': 'application/json' }
        });
        const data = await resp.json();
        if (data.encontrado) {
            mostrarClienteExistente(data.cliente);
        } else {
            mostrarClienteNuevo(doc);
        }
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo consultar el cliente. Intenta de nuevo.', confirmButtonColor: '#6D4C41' });
    }
}

function agregarFilaMaterial(materialId = '', cantidad = '') {
    const contenedor = document.getElementById('listaMateriales');
    const fila = document.createElement('div');
    fila.className = 'fila-material';

    const select = document.createElement('select');
    select.name = 'material_id[]';
    const opcionVacia = document.createElement('option');
    opcionVacia.value = '';
    opcionVacia.textContent = 'Seleccione material...';
    select.appendChild(opcionVacia);

    materiales.forEach(m => {
        // Disponible = stock actual + lo que este arreglo ya tiene reservado (en edición)
        const disponible = m.cantidad + (usadoActual[m.id] || 0);
        const op = document.createElement('option');
        op.value = m.id;
        op.dataset.stock = disponible;
        op.textContent = m.nombre + ' (Disponible: ' + disponible + ' ' + m.unidad + ')';
        if (disponible <= 0) {
            op.disabled = true;
            op.textContent += ' - Agotado';
        }
        select.appendChild(op);
    });

    const input = document.createElement('input');
    input.type = 'number';
    input.name = 'material_cantidad[]';
    input.min = 1;
    input.placeholder = 'Cantidad';

    select.addEventListener('change', () => {
        const stock = select.selectedOptions[0].dataset.stock;
        input.max = stock || '';
        if (stock && !input.value) input.value = 1;
    });

    const quitar = document.createElement('button');
    quitar.type = 'button';
    quitar.className = 'quitar';
    quitar.innerHTML = '<i class="fa-solid fa-xmark"></i>';
    quitar.title = 'Quitar';
    quitar.addEventListener('click', () => fila.remove());

    fila.appendChild(select);
    fila.appendChild(input);
    fila.appendChild(quitar);
    contenedor.appendChild(fila);

    if (materialId !== '') {
        select.value = materialId;
        input.value = cantidad;
        input.max = select.selectedOptions[0] ? (select.selectedOptions[0].dataset.stock || '') : '';
    }
}

function limpiarFormulario() {
    resetCliente();
    document.getElementById('listaMateriales').innerHTML = '';
    agregarFilaMaterial();
}

// Validación en el navegador: no pasar del stock disponible
document.getElementById('formArreglo').addEventListener('submit', (e) => {
    // El cliente debe estar buscado y resuelto antes de guardar
    const docActual = document.getElementById('nc_documento').value.trim();
    if (document.getElementById('modoCliente').value === '' || docActual !== documentoBuscado) {
        e.preventDefault();
        Swal.fire({ icon: 'warning', title: 'Atención', text: 'Escribe el documento del cliente y presiona Buscar antes de guardar.', confirmButtonColor: '#6D4C41' });
        return;
    }

    const filas = document.querySelectorAll('.fila-material');
    for (const fila of filas) {
        const select = fila.querySelector('select');
        const input = fila.querySelector('input');
        if (!select.value && !input.value) continue;

        if (!select.value || !input.value || parseInt(input.value) < 1) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Atención', text: 'Completa el material y la cantidad en cada fila, o quita la fila vacía.', confirmButtonColor: '#6D4C41' });
            return;
        }
        const stock = parseInt(select.selectedOptions[0].dataset.stock);
        if (parseInt(input.value) > stock) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Stock insuficiente', text: 'Solo hay ' + stock + ' disponible(s) de ese material.', confirmButtonColor: '#6D4C41' });
            return;
        }
    }
});

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
            document.getElementById('idEliminar').value = id;
            document.getElementById('formEliminar').submit();
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Campo de documento: Enter busca, al cambiar el texto se reinicia, al salir del campo busca
    const inputDoc = document.getElementById('nc_documento');
    inputDoc.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            buscarCliente();
        }
    });
    inputDoc.addEventListener('input', () => {
        if (inputDoc.value.trim() !== documentoBuscado) {
            resetCliente();
        }
    });
    inputDoc.addEventListener('change', buscarCliente);

    // En edición, se precarga el cliente del arreglo
    if (editando && clienteEditar) {
        inputDoc.value = clienteEditar.documento || '';
        mostrarClienteExistente(clienteEditar);
    }

    // Filas de materiales iniciales
    if (editando) {
        if (filasIniciales.length > 0) {
            filasIniciales.forEach(f => agregarFilaMaterial(f.material_id, f.cantidad));
        } else {
            agregarFilaMaterial();
        }
        document.querySelector('.formulario').scrollIntoView({ behavior: 'smooth' });
    }

    // Buscador
    const buscar = document.getElementById('buscar');
    const filas = document.querySelectorAll('#tablaArreglos tr');
    if (buscar) {
        buscar.addEventListener('keyup', () => {
            const texto = buscar.value.toLowerCase();
            filas.forEach(fila => {
                if (fila.getElementsByTagName('td').length < 3) return;
                fila.style.display = fila.textContent.toLowerCase().includes(texto) ? '' : 'none';
            });
        });
    }
});
</script>

<script src="../../js/arreglos.js"></script>

</body>
</html>