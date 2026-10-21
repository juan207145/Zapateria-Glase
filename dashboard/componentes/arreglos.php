<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>GLASÉ | Gestión de Arreglos</title>

    <link rel="stylesheet" href="../css/arreglos.css">

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

            <li>
                <a href="dashboard.html">
                    <i class="fa-solid fa-house"></i>
                    Inicio
                </a>
            </li>

            <li class="active">
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
                <a href="configuracion.html">
                    <i class="fa-solid fa-gear"></i>
                    Configuración
                </a>
            </li>

        </ul>

    </aside>

    <!------------ CONTENIDO ------------>

    <main>

        <!------------ HEADER ------------>

        <header>

            <div class="titulo">

                <h1>Gestión de Arreglos</h1>

                <p>Registro y seguimiento de reparaciones de calzado.</p>

            </div>

            <button id="nuevo">

                <i class="fa-solid fa-plus"></i>

                Nuevo Arreglo

            </button>

        </header>

        <!------------ FORMULARIO ------------>

        <section class="formulario">

            <h2>Registrar Arreglo</h2>

            <form>

                <div class="fila">

                    <div class="grupo">

                        <label>Nombre del Cliente</label>

                        <input type="text" placeholder="Ingrese el nombre">

                    </div>

                    <div class="grupo">

                        <label>Teléfono</label>

                        <input type="text" placeholder="Ingrese el teléfono">

                    </div>

                </div>

                <div class="fila">

                    <div class="grupo">

                        <label>Tipo de Calzado</label>

                        <select>

                            <option>Seleccione...</option>

                            <option>Tenis</option>

                            <option>Botas</option>

                            <option>Zapato Formal</option>

                            <option>Sandalias</option>

                            <option>Tacones</option>

                        </select>

                    </div>

                    <div class="grupo">

                        <label>Tipo de Arreglo</label>

                        <select>

                            <option>Seleccione...</option>

                            <option>Cambio de Suela</option>

                            <option>Costura</option>

                            <option>Pegado</option>

                            <option>Cambio de Tacón</option>

                            <option>Limpieza</option>

                        </select>

                    </div>

                </div>

                <div class="fila">

                    <div class="grupo">

                        <label>Fecha de Ingreso</label>

                        <input type="date">

                    </div>

                    <div class="grupo">

                        <label>Fecha de Entrega</label>

                        <input type="date">

                    </div>

                </div>

                <div class="fila">

                    <div class="grupo">

                        <label>Valor del Servicio</label>

                        <input type="number" placeholder="$">

                    </div>

                    <div class="grupo">

                        <label>Estado</label>

                        <select>

                            <option>Pendiente</option>

                            <option>En Proceso</option>

                            <option>Finalizado</option>

                        </select>

                    </div>

                </div>

                <div class="grupo">

                    <label>Observaciones</label>

                    <textarea rows="4"
                    placeholder="Descripción del trabajo realizado..."></textarea>

                </div>

                <div class="botones">

                    <button type="button" class="guardar">

                        <i class="fa-solid fa-floppy-disk"></i>

                        Guardar

                    </button>

                    <button type="reset" class="cancelar">

                        <i class="fa-solid fa-trash"></i>

                        Limpiar

                    </button>

                </div>

            </form>

        </section>

        <!------------ TABLA ------------>

        <section class="tabla">

            <div class="encabezado-tabla">

                <h2>Arreglos Registrados</h2>

                <input
                type="text"
                id="buscar"
                placeholder="Buscar arreglo...">

            </div>

            <table>

                <thead>

                    <tr>

                        <th>Cliente</th>

                        <th>Calzado</th>

                        <th>Servicio</th>

                        <th>Entrega</th>

                        <th>Estado</th>

                        <th>Acciones</th>

                    </tr>

                </thead>

                <tbody>

                    <tr>

                        <td>Juan Pérez</td>

                        <td>Tenis Nike</td>

                        <td>Cambio de Suela</td>

                        <td>20/07/2026</td>

                        <td>

                            <span class="pendiente">

                                Pendiente

                            </span>

                        </td>

                        <td>

                            <button class="editar">

                                <i class="fa-solid fa-pen"></i>

                            </button>

                            <button class="eliminar">

                                <i class="fa-solid fa-trash"></i>

                            </button>

                        </td>

                    </tr>

                    <tr>

                        <td>Laura Gómez</td>

                        <td>Botas</td>

                        <td>Costura</td>

                        <td>18/07/2026</td>

                        <td>

                            <span class="proceso">

                                En Proceso

                            </span>

                        </td>

                        <td>

                            <button class="editar">

                                <i class="fa-solid fa-pen"></i>

                            </button>

                            <button class="eliminar">

                                <i class="fa-solid fa-trash"></i>

                            </button>

                        </td>

                    </tr>

                    <tr>

                        <td>Camilo Díaz</td>

                        <td>Zapato Formal</td>

                        <td>Cambio Tacón</td>

                        <td>15/07/2026</td>

                        <td>

                            <span class="finalizado">

                                Finalizado

                            </span>

                        </td>

                        <td>

                            <button class="editar">

                                <i class="fa-solid fa-pen"></i>

                            </button>

                            <button class="eliminar">

                                <i class="fa-solid fa-trash"></i>

                            </button>

                        </td>

                    </tr>

                </tbody>

            </table>

        </section>

    </main>

    <script src="../js/arreglos.js"></script>

</body>

</html>