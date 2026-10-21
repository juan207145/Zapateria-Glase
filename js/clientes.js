

document.addEventListener("DOMContentLoaded", () => {

    //====================================
    // MENÚ ACTIVO (SIDEBAR)
    //====================================
    const menu = document.querySelectorAll(".sidebar li");
    menu.forEach(item => {
        item.addEventListener("click", () => {
            menu.forEach(i => i.classList.remove("active"));
            item.classList.add("active");
        });
    });

    //====================================
    // BOTÓN NUEVO CLIENTE (SCROLL SUAVE)
    //====================================
    const nuevoCliente = document.getElementById("nuevoCliente");
    if (nuevoCliente) {
        nuevoCliente.addEventListener("click", () => {
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        });
    }

    //====================================
    // BUSCADOR EN TIEMPO REAL (FILTRO DE TABLA)
    //====================================
    const buscador = document.getElementById("buscar");
    if (buscador) {
        buscador.addEventListener("keyup", () => {
            let filtro = buscador.value.toLowerCase();
            let filas = document.querySelectorAll("tbody tr");

            filas.forEach(fila => {
                let texto = fila.innerText.toLowerCase();
                if (texto.indexOf(filtro) > -1) {
                    fila.style.display = "";
                } else {
                    fila.style.display = "none";
                }
            });
        });
    }

    //====================================
    // EFECTO INPUTS Y TEXTAREAS (FOCUS / BLUR)
    //====================================
    const inputs = document.querySelectorAll("input, textarea");
    inputs.forEach(input => {
        input.addEventListener("focus", () => {
            input.style.border = "2px solid #6D4C41";
        });
        input.addEventListener("blur", () => {
            input.style.border = "1px solid #ddd";
        });
    });

    //====================================
    // EFECTO TARJETAS (HOVER)
    //====================================
    const cards = document.querySelectorAll(".card");
    cards.forEach(card => {
        card.addEventListener("mouseenter", () => {
            card.style.transform = "translateY(-8px)";
        });
        card.addEventListener("mouseleave", () => {
            card.style.transform = "translateY(0px)";
        });
    });

    //====================================
    // EFECTO BOTONES
    //====================================
    const botones = document.querySelectorAll("button, .botones a");
    botones.forEach(btn => {
        btn.addEventListener("mouseenter", () => {
            btn.style.transform = "scale(1.03)";
        });
        btn.addEventListener("mouseleave", () => {
            btn.style.transform = "scale(1)";
        });
    });

    //====================================
    // RELOJ EN EL HEADER
    //====================================
    const header = document.querySelector("header");
    if (header) {
        const reloj = document.createElement("span");
        reloj.style.marginLeft = "20px";
        reloj.style.fontWeight = "600";
        reloj.style.color = "#6D4C41";
        header.appendChild(reloj);

        function actualizarHora() {
            const fecha = new Date();
            reloj.innerHTML = fecha.toLocaleString("es-CO");
        }

        setInterval(actualizarHora, 1000);
        actualizarHora();
    }

    //====================================
    // CONTADORES ANIMADOS EN TARJETAS
    //====================================
    const contadores = document.querySelectorAll(".card h2");
    contadores.forEach(contador => {
        const objetivo = parseInt(contador.innerText) || 0;
        let numero = 0;

        if (objetivo > 0) {
            const actualizar = () => {
                const incremento = Math.ceil(objetivo / 35);
                if (numero < objetivo) {
                    numero += incremento;
                    contador.innerText = numero;
                    setTimeout(actualizar, 35);
                } else {
                    contador.innerText = objetivo;
                }
            };
            actualizar();
        }
    });

    //====================================
    // ANIMACIÓN DE ENTRADA AL CARGAR
    //====================================
    const formulario = document.querySelector(".formulario");
    const tabla = document.querySelector(".tabla");

    if (formulario) {
        formulario.style.opacity = "0";
        formulario.style.transform = "translateY(30px)";
        setTimeout(() => {
            formulario.style.transition = ".6s";
            formulario.style.opacity = "1";
            formulario.style.transform = "translateY(0px)";
        }, 200);
    }

    cards.forEach((card, index) => {
        card.style.opacity = "0";
        card.style.transform = "translateY(30px)";
        setTimeout(() => {
            card.style.transition = ".5s";
            card.style.opacity = "1";
            card.style.transform = "translateY(0px)";
        }, 350 + (index * 150));
    });

    if (tabla) {
        tabla.style.opacity = "0";
        tabla.style.transform = "translateY(30px)";
        setTimeout(() => {
            tabla.style.transition = ".6s";
            tabla.style.opacity = "1";
            tabla.style.transform = "translateY(0px)";
        }, 800);
    }

});