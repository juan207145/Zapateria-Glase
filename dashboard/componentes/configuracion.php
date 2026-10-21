<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>GLASÉ | Configuración</title>

    <link rel="stylesheet" href="../css/configuracion.css">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<!---------------- SIDEBAR ---------------->

<aside class="sidebar">

    <div class="logo">

        <i class="fa-solid fa-shoe-prints"></i>

        <h2>GLASÉ</h2>

    </div>

    <ul>

        <li>
            <a href="dashboard.html">
                <i class="fa-solid fa-house"></i>
                Inicio
            </a>
        </li>

        <li>
            <a href="arreglos.html">
                <i class="fa-solid fa-screwdriver-wrench"></i>
                Arreglos
            </a>
        </li>

        <li>
            <a href="inventario.html">
                <i class="fa-solid fa-boxes-stacked"></i>
                Inventario
            </a>
        </li>

        <li>
            <a href="mercancia.html">
                <i class="fa-solid fa-box-open"></i>
                Mercancía
            </a>
        </li>

        <li>
            <a href="clientes.html">
                <i class="fa-solid fa-users"></i>
                Clientes
            </a>
        </li>

        <li>
            <a href="reportes.html">
                <i class="fa-solid fa-chart-column"></i>
                Reportes
            </a>
        </li>

        <li class="active">
            <a href="configuracion.html">
                <i class="fa-solid fa-gear"></i>
                Configuración
            </a>
        </li>

    </ul>

</aside>

<!---------------- MAIN ---------------->

<main>

<header>

    <div>

        <h1>Configuración del Sistema</h1>

        <p>Administra la información del usuario y las preferencias generales de GLASÉ.</p>

    </div>

</header>

<!---------------- PERFIL ---------------->

<section class="perfil">

    <h2>

        <i class="fa-solid fa-user"></i>

        Información del Usuario

    </h2>

    <form>

        <div class="fila">

            <div class="grupo">

                <label>Nombre Completo</label>

                <input
                type="text"
                value="Administrador">

            </div>

            <div class="grupo">

                <label>Correo Electrónico</label>

                <input
                type="email"
                value="admin@glase.com">

            </div>

        </div>

        <div class="fila">

            <div class="grupo">

                <label>Usuario</label>

                <input
                type="text"
                value="Administrador">

            </div>

            <div class="grupo">

                <label>Teléfono</label>

                <input
                type="text"
                placeholder="Ingrese teléfono">

            </div>

        </div>

    </form>

</section>

<!---------------- SEGURIDAD ---------------->

<section class="seguridad">

    <h2>

        <i class="fa-solid fa-lock"></i>

        Seguridad

    </h2>

    <form>

        <div class="fila">

            <div class="grupo">

                <label>Contraseña Actual</label>

                <input
                type="password"
                placeholder="********">

            </div>

            <div class="grupo">

                <label>Nueva Contraseña</label>

                <input
                type="password"
                placeholder="********">

            </div>

        </div>

        <div class="fila">

            <div class="grupo">

                <label>Confirmar Contraseña</label>

                <input
                type="password"
                placeholder="********">

            </div>

        </div>

    </form>

</section>

<!---------------- PREFERENCIAS ---------------->

<section class="preferencias">

    <h2>

        <i class="fa-solid fa-sliders"></i>

        Preferencias

    </h2>

    <div class="opciones">

        <div class="opcion">

            <label>

                <input type="checkbox">

                Activar Notificaciones

            </label>

        </div>

        <div class="opcion">

            <label>

                <input type="checkbox">

                Modo Oscuro

            </label>

        </div>

        <div class="opcion">

            <label>

                <input type="checkbox" checked>

                Recordar Inicio de Sesión

            </label>

        </div>

        <div class="opcion">

            <label>

                <input type="checkbox">

                Actualizaciones Automáticas

            </label>

        </div>

    </div>

</section>

<!---------------- INFORMACIÓN DEL SISTEMA ---------------->

<section class="informacion">

    <h2>

        <i class="fa-solid fa-circle-info"></i>

        Información del Sistema

    </h2>

    <table>

        <tr>

            <td>Versión</td>

            <td>1.0</td>

        </tr>

        <tr>

            <td>Nombre</td>

            <td>Sistema GLASÉ</td>

        </tr>

        <tr>

            <td>Desarrollador</td>

            <td>Juan Sebastián Rojas</td>

        </tr>

        <tr>

            <td>Framework</td>

            <td>HTML5 - CSS3 - JavaScript</td>

        </tr>

        <tr>

            <td>Base de Datos</td>

            <td>MySQL</td>

        </tr>

    </table>

</section>

<!---------------- BOTONES ---------------->

<section class="acciones">

    <button class="guardar">

        <i class="fa-solid fa-floppy-disk"></i>

        Guardar Cambios

    </button>

    <button class="restablecer">

        <i class="fa-solid fa-rotate-left"></i>

        Restablecer

    </button>

    <button class="cerrar">

        <i class="fa-solid fa-right-from-bracket"></i>

        Cerrar Sesión

    </button>

</section>

</main>

<script src="../js/configuracion.js"></script>

</body>

</html>