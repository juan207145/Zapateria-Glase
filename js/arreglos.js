/*=========================================
        SISTEMA GLASÉ
        MÓDULO ARREGLOS (solo efectos visuales)
        La lógica (guardar, editar, eliminar, buscar,
        materiales y clientes) está en arreglos.php
=========================================*/


//==============================
// ANIMACIÓN DE ENTRADA
//==============================

window.addEventListener("load", () => {

    const bloques = document.querySelectorAll(".panel-stock, .tabla");

    bloques.forEach((bloque, i) => {

        bloque.style.opacity = "0";
        bloque.style.transform = "translateY(30px)";

        setTimeout(() => {

            bloque.style.transition = ".6s";
            bloque.style.opacity = "1";
            bloque.style.transform = "translateY(0px)";

        }, 200 + i * 250);

    });

});


//==============================
// EFECTO EN INPUTS (también en los creados dinámicamente)
//==============================

document.addEventListener("focusin", (e) => {

    if (e.target.matches("input, select, textarea")) {

        e.target.style.border = "2px solid #6D4C41";

    }

});

document.addEventListener("focusout", (e) => {

    if (e.target.matches("input, select, textarea")) {

        e.target.style.border = "1px solid #ddd";

    }

});


//==============================
// HOVER EN BOTONES
//==============================

document.addEventListener("mouseover", (e) => {

    const btn = e.target.closest("button");

    if (btn) {

        btn.style.transform = "translateY(-2px)";

    }

});

document.addEventListener("mouseout", (e) => {

    const btn = e.target.closest("button");

    if (btn) {

        btn.style.transform = "translateY(0px)";

    }

});


//==============================
// RELOJ EN EL HEADER
//==============================

const header = document.querySelector("header");

if (header) {

    const reloj = document.createElement("span");

    reloj.style.marginLeft = "20px";
    reloj.style.color = "#6D4C41";
    reloj.style.fontWeight = "600";

    header.appendChild(reloj);

    function actualizarHora() {

        reloj.textContent = new Date().toLocaleString();

    }

    setInterval(actualizarHora, 1000);

    actualizarHora();

}


//==============================
// FIN DEL SCRIPT
//==============================