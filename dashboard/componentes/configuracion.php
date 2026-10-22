<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../../conexion.php'; 

// Control de acceso a la sesión y verificación exclusiva para Administrador
if (!isset($_SESSION['Usuario'])) {
    header("Location: ../../index.php");
    exit();
}

$usuario = $_SESSION['Usuario'];
$rol = $_SESSION['Rol'];

if (strtolower($rol) !== 'administrador') {
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
    echo "<body style='background:#F5F3F0;'></body>";
    echo "<script>
        window.addEventListener('DOMContentLoaded', () => {
            Swal.fire({
                icon: 'error',
                title: 'Acceso Denegado',
                text: 'Solo el Administrador puede acceder al módulo de configuración.',
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


// ---------------- 1. ELIMINAR USUARIO ----------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['eliminar_usuario'])) {
    $id = $_GET['eliminar_usuario'];

    // Evitar que el admin principal se elimine a sí mismo por seguridad
    $cons_self = $conexion->prepare("SELECT Usuario FROM iniciodesesion WHERE Id = ?");
    $cons_self->bind_param("i", $id);
    $cons_self->execute();
    $res_self = $cons_self->get_result()->fetch_assoc();
    $cons_self->close();

    if ($res_self && $res_self['Usuario'] === $usuario) {
        $alerta_tipo = "error";
        $alerta_mensaje = "No puedes eliminar tu propia cuenta de Administrador activa.";
        $redireccionar = "configuracion.php";
    } else {
        $stmt = $conexion->prepare("DELETE FROM iniciodesesion WHERE Id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $alerta_tipo = "success";
            $alerta_mensaje = "¡Usuario eliminado correctamente!";
        } else {
            $alerta_tipo = "error";
            $alerta_mensaje = "Error al eliminar usuario: " . $conexion->error;
        }
        $stmt->close();
        $redireccionar = "configuracion.php";
    }
}

// ---------------- 2. PROCESAR GUARDADO DE NUEVO USUARIO ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_usuario'])) {
    $nuevo_usuario = trim($_POST['Usuario']);
    $nueva_contra = trim($_POST['Contraseña']);
    $nuevo_rol = trim($_POST['Rol']);

    if (!empty($nuevo_usuario) && !empty($nueva_contra) && !empty($nuevo_rol)) {
        // Verificar si el usuario ya existe
        $verif = $conexion->prepare("SELECT Id FROM iniciodesesion WHERE Usuario = ?");
        $verif->bind_param("s", $nuevo_usuario);
        $verif->execute();
        $verif->store_result();

        if ($verif->num_rows > 0) {
            $alerta_tipo = "warning";
            $alerta_mensaje = "El nombre de usuario ya está registrado. Por favor elija otro.";
        } else {
            $contra_enc = ($nueva_contra);
            $stmt = $conexion->prepare("INSERT INTO iniciodesesion (Usuario, Contraseña, Rol) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $nuevo_usuario, $contra_enc, $nuevo_rol);
            
            if ($stmt->execute()) {
                $alerta_tipo = "success";
                $alerta_mensaje = "¡Nuevo usuario creado correctamente!";
            } else {
                $alerta_tipo = "error";
                $alerta_mensaje = "Error al registrar usuario: " . $conexion->error;
            }
            $stmt->close();
        }
        $verif->close();
        $redireccionar = "configuracion.php";
    } else {
        $alerta_tipo = "warning";
        $alerta_mensaje = "Por favor completa todos los campos obligatorios.";
    }
}

// ---------------- 3. PROCESAR ACTUALIZACIÓN DE USUARIO ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_usuario'])) {
    $id = $_POST['id'];
    $nuevo_usuario = trim($_POST['Usuario']);
    $nueva_contra = trim($_POST['Contraseña']);
    $nuevo_rol = trim($_POST['Rol']);
   
    if (!empty($nuevo_usuario) && !empty($nuevo_rol)) {
        if (!empty($nueva_contra)) {
            $contra_enc =($nueva_contra);
            $stmt = $conexion->prepare("UPDATE iniciodesesion SET Usuario = ?, Contraseña = ?, Rol = ? WHERE Id = ?");
            $stmt->bind_param("sssi", $nuevo_usuario, $contra_enc, $nuevo_rol, $id);
        } else {
            // Si dejó la contraseña vacía, mantenemos la anterior
            $stmt = $conexion->prepare("UPDATE iniciodesesion SET Usuario = ?, Rol = ? WHERE Id = ?");
            $stmt->bind_param("ssi", $nuevo_usuario, $nuevo_rol, $id);
        }

        if ($stmt->execute()) {
            $alerta_tipo = "success";
            $alerta_mensaje = "¡Usuario actualizado correctamente!";
        } else {
            $alerta_tipo = "error";
            $alerta_mensaje = "Error al actualizar usuario: " . $conexion->error;
        }
        $stmt->close();
        $redireccionar = "configuracion.php";
    } else {
        $alerta_tipo = "warning";
        $alerta_mensaje = "Por favor completa los campos obligatorios.";
    }
} 

// ---------------- 4. CARGAR DATOS PARA EDICIÓN ----------------
$editando = false;
$edit_id = $edit_usuario = $edit_rol = "";

if (isset($_GET['editar_id'])) {
    $id_editar = $_GET['editar_id'];
    $stmt = $conexion->prepare("SELECT * FROM iniciodesesion WHERE Id = ?");
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($usu_edit = $res->fetch_assoc()) {
        $editando = true;
        $edit_id = $usu_edit['Id'];
        $edit_usuario = $usu_edit['Usuario'];
        $edit_rol = $usu_edit['Rol'];
    }
    $stmt->close();
}

// ---------------- 5. CONSULTAS DE ESTADÍSTICAS Y LISTADO ----------------
$total_usuarios_q = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM iniciodesesion");
$total_usuarios = mysqli_fetch_assoc($total_usuarios_q)['total'] ?? 0;

$total_admins_q = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM iniciodesesion WHERE LOWER(Rol) = 'administrador'");
$total_admins = mysqli_fetch_assoc($total_admins_q)['total'] ?? 0;

$resultado_usuarios = mysqli_query($conexion, "SELECT * FROM iniciodesesion ORDER BY Id DESC");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GLASÉ | Configuración y Usuarios</title>
    <link rel="stylesheet" href="../../css/configuracion.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .formulario-oculto {
            display: none !important;
        }
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

<!---------------- SIDEBAR ---------------->
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
        <li>
            <a href="clientes.php">
                <i class="fa-solid fa-users"></i>
                Clientes
            </a>
        </li>
        <li class="active">
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

<!---------------- MAIN ---------------->
<main>
<header>
    <div>
        <h1>Configuración y Gestión de Usuarios</h1>
        <p>Panel exclusivo del Administrador para crear, editar y administrar los accesos al sistema.</p>
    </div>
    <button class="btnAgregar" onclick="toggleFormulario()">
        <i class="fa-solid fa-user-plus"></i>
        Nuevo Usuario
    </button>
</header>

<!---------------- FORMULARIO (CREAR O EDITAR USUARIO) ---------------->
<section class="formulario <?php echo $editando ? '' : 'formulario-oculto'; ?>">
    <h2><?php echo $editando ? 'Editar Usuario' : 'Registrar Nuevo Usuario'; ?></h2>
    <form action="configuracion.php" method="POST">
        <?php if ($editando): ?>
            <input type="hidden" name="id" value="<?php echo $edit_id; ?>">
        <?php endif; ?>

        <div class="fila">
            <div class="grupo">
                <label>Nombre de Usuario *</label>
                <input type="text" name="Usuario" placeholder="Ej: juan_admin" value="<?php echo htmlspecialchars($edit_usuario); ?>" required>
            </div>
            <div class="grupo">
                <label>Contraseña *<?php echo $editando ? ' (Dejar en blanco para mantener la actual)' : ''; ?></label>
                <input type="password" name="Contraseña" placeholder="********" <?php echo $editando ? '' : 'required'; ?>>
            </div>
        </div>

        <div class="fila">
            <div class="grupo">
                <label>Rol del Sistema *</label>
                <select name="Rol" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; background: white;">
                    <option value="" disabled selected>Seleccione el rol</option>
                    <option value="Administrador" <?php if($edit_rol=='Administrador' || $edit_rol=='administrador') echo 'selected'; ?>>Administrador</option>
                    <option value="Empleado" <?php if($edit_rol=='Empleado' || $edit_rol=='empleado') echo 'selected'; ?>>Empleado</option>
                </select>
            </div>
        </div>

        <div class="botones" style="margin-top: 20px;">
            <?php if ($editando): ?>
                <button type="submit" name="editar_usuario" class="guardar">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Actualizar Usuario
                </button>
                <a href="configuracion.php" class="cancelar" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center; padding:12px 20px; border-radius:8px; color:white; background:#e74c3c;">
                    <i class="fa-solid fa-xmark" style="margin-right:6px;"></i>
                    Cancelar
                </a>
            <?php else: ?>
                <button type="submit" name="guardar_usuario" class="guardar">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Guardar Usuario
                </button>
                <button type="button" class="cancelar" onclick="toggleFormulario()">
                    <i class="fa-solid fa-xmark"></i>
                    Cancelar
                </button>
            <?php endif; ?>
        </div>
    </form>
</section>

<!---------------- TARJETAS ESTADÍSTICAS ---------------->
<section class="cards" style="grid-template-columns: repeat(2, 1fr); margin-bottom: 30px;">
    <div class="card">
        <i class="fa-solid fa-users-gear"></i>
        <div>
            <h2><?php echo $total_usuarios; ?></h2>
            <p>Usuarios Registrados</p>
        </div>
    </div>
    <div class="card">
        <i class="fa-solid fa-user-shield"></i>
        <div>
            <h2><?php echo $total_admins; ?></h2>
            <p>Administradores</p>
        </div>
    </div>
</section>

<!---------------- BUSCADOR ---------------->
<section class="busqueda" style="margin-bottom: 20px;">
    <div class="buscar" style="display: flex; align-items: center; background: white; padding: 12px 20px; border-radius: 10px; box-shadow: var(--sombra);">
        <i class="fa-solid fa-magnifying-glass" style="color: var(--gris); margin-right: 12px;"></i>
        <input type="text" id="buscar" placeholder="Buscar usuario o rol..." style="border: none; outline: none; width: 100%; font-size: 14px;">
    </div>
</section>

<!---------------- TABLA DE USUARIOS ---------------->
<section class="panel">
<div class="titulo">
    <h2>Listado de Usuarios del Sistema</h2>
</div>
<table>
<thead>
    <tr>
        <th>ID</th>
        <th>Usuario</th>
        <th>Rol</th>
        <th>Acciones</th>
    </tr>
</thead>
<tbody id="tablaUsuarios">
    <?php if ($resultado_usuarios && mysqli_num_rows($resultado_usuarios) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($resultado_usuarios)): ?>
            <tr>
                <td>#<?php echo $row['Id']; ?></td>
                <td><i class="fa-solid fa-user" style="margin-right: 8px; color: var(--principal);"></i><?php echo htmlspecialchars($row['Usuario']); ?></td>
                <td>
                    <span class="estado" style="padding: 6px 12px; border-radius: 6px; color: white; background-color: <?php echo (strtolower($row['Rol']) === 'administrador') ? '#6D4C41' : '#3498db'; ?>;">
                        <?php echo htmlspecialchars($row['Rol']); ?>
                    </span>
                </td>
                <td>
                    <a href="configuracion.php?editar_id=<?php echo $row['Id']; ?>" class="editar" title="Editar">
                        <i class="fa-solid fa-pen"></i>
                    </a>
                    <a href="#" onclick="confirmarEliminacion(<?php echo $row['Id']; ?>)" class="eliminar" title="Eliminar">
                        <i class="fa-solid fa-trash"></i>
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="4" style="text-align: center;">No hay usuarios registrados en el sistema.</td>
        </tr>
    <?php endif; ?>
</tbody>
</table>
</section>

<!---------------- FOOTER ---------------->
<footer>
<p>© 2026 Sistema GLASÉ | Módulo de Configuración y Seguridad</p>
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
        text: "¡El usuario seleccionado perderá acceso al sistema de forma permanente!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#6D4C41',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'configuracion.php?eliminar_usuario=' + id;
        }
    })
}

// Buscador en tiempo real para la tabla de usuarios
document.addEventListener("DOMContentLoaded", () => {
    const inputBuscar = document.getElementById("buscar");
    if (inputBuscar) {
        inputBuscar.addEventListener("keyup", function() {
            let filtro = inputBuscar.value.toLowerCase().trim();
            let filas = document.querySelectorAll("#tablaUsuarios tr");

            filas.forEach(fila => {
                let textoFila = fila.textContent.toLowerCase();
                if (textoFila.includes(filtro)) {
                    fila.style.display = "";
                } else {
                    fila.style.display = "none";
                }
            });
        });
    }
});
</script>

</body>
</html>