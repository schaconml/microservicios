// Utilidad de interfaz: solo renderiza alertas, insignias semánticas y diálogos.
// Nunca toca estilos en línea: todo cambio visual usa classList o el atributo hidden.
export class UiFeedback {
    // Muestra un mensaje de éxito dentro del contenedor de alertas indicado.
    mostrarExito(contenedor, mensaje) {
        this.#mostrar(contenedor, mensaje, 'alerta--exito');
    }

    // Muestra un mensaje de error dentro del contenedor de alertas indicado.
    mostrarError(contenedor, mensaje) {
        const texto = mensaje instanceof Error ? mensaje.message : String(mensaje);
        this.#mostrar(contenedor, texto, 'alerta--error');
    }

    // Vacía el contenedor de alertas.
    limpiar(contenedor) {
        if (contenedor) {
            contenedor.replaceChildren();
        }
    }

    // Crea una insignia semántica (<mark>) con clase según el estado.
    insignia(estado) {
        const marca = document.createElement('mark');
        const limpio = String(estado).toLowerCase().replace(/[^a-z]/g, '');
        marca.classList.add('insignia', 'insignia--' + limpio);
        marca.textContent = estado;
        return marca;
    }

    // Crea una fecha semántica (<time>) con su atributo datetime.
    fecha(valor) {
        const tiempo = document.createElement('time');
        if (valor === null || valor === undefined || valor === '') {
            tiempo.textContent = '—';
            return tiempo;
        }
        const texto = String(valor).slice(0, 10);
        tiempo.setAttribute('datetime', texto);
        tiempo.textContent = texto;
        return tiempo;
    }

    // Escapa texto para interpolarlo con seguridad en plantillas HTML.
    escapar(valor) {
        return String(valor ?? '').replace(/[&<>"']/g, (caracter) => {
            const mapa = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
            return mapa[caracter];
        });
    }

    // Abre un <dialog> de confirmación con el mensaje indicado.
    pedirConfirmacion(dialogo, parrafo, mensaje) {
        parrafo.textContent = mensaje;
        if (!dialogo.open) {
            dialogo.showModal();
        }
    }

    #mostrar(contenedor, mensaje, modificador) {
        if (!contenedor) {
            return;
        }
        contenedor.replaceChildren();
        const parrafo = document.createElement('p');
        parrafo.classList.add('alerta', modificador);
        parrafo.textContent = mensaje;
        contenedor.append(parrafo);
    }
}
