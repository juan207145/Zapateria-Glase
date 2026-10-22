<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" 
    integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <title>GLASÉ | Panel Principal</title>

    <link rel="stylesheet" href="../css/dashboard.css">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<aside class="sidebar">

    <div class="logo">
        <i class="fa-solid fa-shoe-prints"></i>
        <h2>GLASÉ</h2>
    </div>

    <ul>
        <li class="active">
            <!-- Si dashboard.php está dentro de la carpeta dashboard -->
            <a href="dashboard.php">
                <i class="fa-solid fa-house"></i>
                Panel Principal
            </a>
        </li>

        <li>
            <!-- Si los demás componentes están en una subcarpeta llamada componentes -->
            <a href="componentes/arreglos.php">
                <i class="fa-solid fa-screwdriver-wrench"></i>
                Arreglos
            </a>
        </li>

        <li>
            <a href="componentes/materiales.php">
                <i class="fa-solid fa-boxes-stacked"></i>
                Materiales
            </a>
        </li>
 
        <li>
            <a href="componentes/mercancia.php">
                <i class="fa-solid fa-box-open"></i>
                Mercancía
            </a>
        </li>

        <li>
            <a href="componentes/clientes.php">
                <i class="fa-solid fa-users"></i>
                Clientes
            </a>
        </li>

  

        <li>
            <a href="componentes/configuracion.php">
                <i class="fa-solid fa-gear"></i>
                Configuración
            </a>
        </li>

        <li>
            <a href="cerrarsesion.php">
                <i class="fa-solid fa-right-from-bracket"></i>
                Cerrar Sesión
            </a>
        </li>
    </ul>

</aside>

<!-------------------- MAIN -------------------->

<main>

<!-------------------- HEADER -------------------->

<header>

    <div>

        <h1>Panel Principal</h1>

        <p>Bienvenido al sistema de gestión de la Zapatería GLASÉ.</p>

    </div>

    <div class="usuario">

        <i class="fa-solid fa-user-circle"></i>

        <span>Administrador</span>

    </div>

</header>

<!-------------------- TARJETAS -------------------->

<section class="cards">

    <div class="card">

        <i class="fa-solid fa-screwdriver-wrench"></i>

        <div>

            <h2>58</h2>

            <p>Arreglos Activos</p>

        </div>

    </div>

    <div class="card">

        <i class="fa-solid fa-box-open"></i>

        <div>

            <h2>245</h2>

            <p>Productos</p>

        </div>

    </div>

    <div class="card">

        <i class="fa-solid fa-users"></i>

        <div>

            <h2>132</h2>

            <p>Clientes</p>

        </div>

    </div>

    <div class="card">

        <i class="fa-solid fa-dollar-sign"></i>

        <div>

            <h2>$4.250.000</h2>

            <p>Ventas del Mes</p>

        </div>

    </div>

</section>

<!-------------------- RESUMEN -------------------->

<section class="dashboard-grid">

<!-------------------- GRÁFICA -------------------->

<div class="panel grafica">

    <div class="titulo">

        <h2>Ventas Mensuales</h2>

    </div>

    <canvas id="graficaVentas"></canvas>

</div>

<!-------------------- ACTIVIDAD RECIENTE -------------------->

<div class="panel actividad">

    <div class="titulo">

        <h2>Actividad Reciente</h2>

    </div>

    <ul>

        <li>
            <i class="fa-solid fa-circle-check"></i>
            Se registró un nuevo arreglo.
        </li>

        <li>
            <i class="fa-solid fa-circle-check"></i>
            Nuevo cliente agregado.
        </li>

        <li>
            <i class="fa-solid fa-circle-check"></i>
            Entrada de mercancía registrada.
        </li>

        <li>
            <i class="fa-solid fa-circle-check"></i>
            Inventario actualizado.
        </li>

        <li>
            <i class="fa-solid fa-circle-check"></i>
            Reporte generado correctamente.
        </li>

    </ul>

</div>

</section>

<!-------------------- TABLA DE ARREGLOS -------------------->

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

            <tr>

                <td>Juan Pérez</td>

                <td>Cambio de Suela</td>

                <td>08/07/2026</td>

                <td>10/07/2026</td>

                <td><span class="estado proceso">En Proceso</span></td>

            </tr>

            <tr>

                <td>Laura Gómez</td>

                <td>Cambio de Tacón</td>

                <td>07/07/2026</td>

                <td>09/07/2026</td>

                <td><span class="estado listo">Listo</span></td>

            </tr>

            <tr>

                <td>Carlos Díaz</td>

                <td>Costura General</td>

                <td>06/07/2026</td>

                <td>11/07/2026</td>

                <td><span class="estado pendiente">Pendiente</span></td>

            </tr>
                        <tr>

                <td>María Rodríguez</td>

                <td>Pegado de Suela</td>

                <td>05/07/2026</td>

                <td>08/07/2026</td>

                <td><span class="estado listo">Entregado</span></td>

            </tr>

            <tr>

                <td>Andrés Torres</td>

                <td>Teñido de Cuero</td>

                <td>04/07/2026</td>

                <td>09/07/2026</td>

                <td><span class="estado proceso">En Proceso</span></td>

            </tr>

        </tbody>

    </table>

</section>

<!-------------------- PANEL INFERIOR -------------------->

<section class="dashboard-grid">

<!-------------------- INVENTARIO -------------------->

<div class="panel">

    <div class="titulo">

        <h2>Inventario General</h2>

    </div>

    <table>

        <thead>

            <tr>

                <th>Material</th>

                <th>Disponible</th>

            </tr>

        </thead>

        <tbody>

            <tr>

                <td>Suelas</td>

                <td>120</td>

            </tr>

            <tr>

                <td>Pegante</td>

                <td>38</td>

            </tr>

            <tr>

                <td>Hilos</td>

                <td>85</td>

            </tr>

            <tr>

                <td>Plantillas</td>

                <td>64</td>

            </tr>

            <tr>

                <td>Cremalleras</td>

                <td>42</td>

            </tr>

        </tbody>

    </table>

</div>

<!-------------------- ALERTAS -------------------->

<div class="panel">

    <div class="titulo">

        <h2>Alertas del Sistema</h2>

    </div>

    <ul class="alertas">

        <li>

            <i class="fa-solid fa-triangle-exclamation"></i>

            Pegante con bajo inventario.

        </li>

        <li>

            <i class="fa-solid fa-box"></i>

            Llegó nueva mercancía.

        </li>

        <li>

            <i class="fa-solid fa-clock"></i>

            Hay 8 arreglos próximos a entregar.

        </li>

        <li>

            <i class="fa-solid fa-user-plus"></i>

            Se registraron 3 nuevos clientes.

        </li>

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

                <th>Ciudad</th>

                <th>Estado</th>

            </tr>

        </thead>

        <tbody>

            <tr>

                <td>Juan Pérez</td>

                <td>3104589632</td>

                <td>Bogotá</td>

                <td>Activo</td>

            </tr>

            <tr>

                <td>Laura Gómez</td>

                <td>3206547896</td>

                <td>Bogotá</td>

                <td>Activo</td>

            </tr>

            <tr>

                <td>Camilo Díaz</td>

                <td>3158741236</td>

                <td>Soacha</td>

                <td>Activo</td>

            </tr>

            <tr>

                <td>María Rodríguez</td>

                <td>3189547812</td>

                <td>Bogotá</td>

                <td>Activo</td>

            </tr>

        </tbody>

    </table>

</section>

<!-------------------- ENTREGAS DEL DÍA -------------------->

<section class="panel">

    <div class="titulo">

        <h2>Entregas Programadas para Hoy</h2>

    </div>

    <table>

        <thead>

            <tr>

                <th>Cliente</th>

                <th>Trabajo</th>

                <th>Hora</th>

            </tr>

        </thead>

        <tbody>

            <tr>

                <td>Laura Gómez</td>

                <td>Cambio de Tacón</td>

                <td>10:00 AM</td>

            </tr>

            <tr>

                <td>Juan Pérez</td>

                <td>Cambio de Suela</td>

                <td>2:00 PM</td>

            </tr>

            <tr>

                <td>María Rodríguez</td>

                <td>Pegado de Suela</td>

                <td>5:00 PM</td>

            </tr>

        </tbody>

    </table>

</section>

<!-------------------- FOOTER -------------------->

<footer>

    <p>

        © 2026 Sistema GLASÉ | Gestión Integral para Zapaterías

    </p>

</footer>

</main>

<!-- Librería para las gráficas -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="../js/dashboard.js"></script>

</body>

</html>