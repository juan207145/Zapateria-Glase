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