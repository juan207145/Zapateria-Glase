//==============================
// NOTIFICACIONES (campana y panel agrupado)
//==============================
(function () {
    if (!window.GLASE_ALERTAS) return;
    const alertas = window.GLASE_ALERTAS.lista;
    const automatico = window.GLASE_ALERTAS.automatico;

    const globo = document.getElementById('campanaGlobo');
    const texto = document.getElementById('campanaTexto');
    const icono = document.getElementById('campanaIcono');
    const boton = document.getElementById('campanaBtn');
    const panelNotif = document.getElementById('notifPanel');
    let cancelado = false;

    function alternarPanel(forzar) {
        cancelado = true;
        globo.classList.remove('visible');
        const abrir = (typeof forzar === 'boolean') ? forzar : !panelNotif.classList.contains('abierto');
        panelNotif.classList.toggle('abierto', abrir);
    }

    boton.addEventListener('click', () => alternarPanel());
    globo.addEventListener('click', () => alternarPanel(true));
    document.getElementById('notifCerrar').addEventListener('click', () => alternarPanel(false));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') alternarPanel(false); });

    // Cada grupo se puede plegar o desplegar
    document.querySelectorAll('.notif-grupo-titulo').forEach(btn => {
        btn.addEventListener('click', () => btn.parentElement.classList.toggle('cerrado'));
    });

    // Ir a la tabla de alertas del panel principal
    document.getElementById('notifVerTabla').addEventListener('click', e => {
        e.preventDefault();
        alternarPanel(false);
        const tabla = document.getElementById('panel-alertas');
        if (tabla) {
            tabla.scrollIntoView({ behavior: 'smooth', block: 'center' });
            tabla.classList.add('resaltado');
            setTimeout(() => tabla.classList.remove('resaltado'), 2500);
        }
    });

    // Muestra las alertas de a una (una sola pasada por inicio de sesión)
    function mostrar(i) {
        if (cancelado || i >= alertas.length) {
            globo.classList.remove('visible');
            return;
        }
        const a = alertas[i];
        texto.textContent = a.texto;
        globo.className = 'campana-globo visible ' + a.nivel;
        icono.className = 'fa-solid ' + (a.nivel === 'critica' ? 'fa-circle-xmark' : 'fa-triangle-exclamation');
        setTimeout(() => {
            globo.classList.remove('visible');
            setTimeout(() => mostrar(i + 1), 500);
        }, 4000);
    }

    if (automatico) {
        setTimeout(() => mostrar(0), 800);
    }
})();

//==============================
// RELOJ DEL ENCABEZADO
//==============================
// Reloj del encabezado
function actualizarReloj() {
    const el = document.getElementById('reloj');
    if (el) {
        el.textContent = new Date().toLocaleString('es-CO');
    }
}
actualizarReloj();
setInterval(actualizarReloj, 1000);

//==============================
// MENÚ RESPONSIVO (botón de tres rayas)
//==============================
document.addEventListener('DOMContentLoaded', function () {
    const menu = document.querySelector('.sidebar');
    const boton = document.getElementById('menuToggle');
    const fondo = document.getElementById('menuOverlay');
    if (!menu || !boton) return;
    function cambiar(abrir) {
        menu.classList.toggle('abierto', abrir);
        fondo.classList.toggle('visible', abrir);
        boton.innerHTML = abrir ? '<i class="fa-solid fa-xmark"></i>' : '<i class="fa-solid fa-bars"></i>';
    }
    boton.addEventListener('click', () => cambiar(!menu.classList.contains('abierto')));
    fondo.addEventListener('click', () => cambiar(false));
    menu.querySelectorAll('a').forEach(a => a.addEventListener('click', () => cambiar(false)));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') cambiar(false); });
    window.addEventListener('resize', () => { if (window.innerWidth > 900) cambiar(false); });
});