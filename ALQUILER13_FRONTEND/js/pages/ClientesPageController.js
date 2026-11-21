import { AppConfig } from '../config.js';
import { HttpClient } from '../core/HttpClient.js';
import { UiFeedback } from '../core/UiFeedback.js';
import { ClientesApi } from '../api/ClientesApi.js';

// Controla la pantalla de clientes: listado, registro, edición y eliminación.
// Recibe Api y UiFeedback por constructor.
export class ClientesPageController {
    #api;
    #feedback;
    #idEnEdicion;
    #idPendiente;

    constructor(clientesApi, feedback) {
        this.#api = clientesApi;
        this.#feedback = feedback;
        this.#idEnEdicion = null;
        this.#idPendiente = null;
    }

    init() {
        const formulario = document.getElementById('form-cliente');
        formulario.addEventListener('submit', (evento) => this.#guardar(evento));
        formulario.addEventListener('reset', () => this.#cancelarEdicion());
        document.getElementById('tabla-clientes').addEventListener('click', (evento) => this.#accionFila(evento));
        document.getElementById('dialogo-confirmar').addEventListener('close', (evento) => this.#resolverConfirmacion(evento));
        this.#cargar();
    }

    async #cargar() {
        const alertas = document.querySelector('[data-alertas]');
        try {
            const respuesta = await this.#api.listar();
            this.#feedback.limpiar(alertas);
            this.#pintar(respuesta.data || []);
        } catch (error) {
            this.#feedback.mostrarError(alertas, error);
        }
    }

    #pintar(clientes) {
        const cuerpo = document.getElementById('tabla-clientes');
        const vacio = document.getElementById('sin-registros');
        cuerpo.replaceChildren();
        vacio.hidden = clientes.length > 0;
        for (const cliente of clientes) {
            const fila = document.createElement('tr');
            const celdaId = document.createElement('th');
            celdaId.setAttribute('scope', 'row');
            const dato = document.createElement('data');
            dato.value = cliente.id;
            dato.textContent = '#' + cliente.id;
            celdaId.append(dato);
            fila.append(celdaId);
            for (const clave of ['nombre', 'telefono', 'correo', 'numero_licencia']) {
                const celda = document.createElement('td');
                celda.textContent = cliente[clave] ?? '—';
                fila.append(celda);
            }
            const acciones = document.createElement('td');
            acciones.append(
                this.#boton('Editar', 'editar', cliente.id, 'boton-secundario'),
                this.#boton('Eliminar', 'eliminar', cliente.id, 'boton-peligro'),
            );
            fila.append(acciones);
            cuerpo.append(fila);
        }
    }

    #boton(etiqueta, accion, id, clase) {
        const boton = document.createElement('button');
        boton.type = 'button';
        boton.textContent = etiqueta;
        boton.classList.add(clase);
        boton.setAttribute('data-accion', accion);
        boton.setAttribute('data-id', id);
        return boton;
    }

    async #guardar(evento) {
        evento.preventDefault();
        const alertas = document.querySelector('[data-alertas]');
        const datos = {
            nombre: document.getElementById('campo-nombre').value.trim(),
            telefono: document.getElementById('campo-telefono').value.trim(),
            correo: document.getElementById('campo-correo').value.trim(),
            numero_licencia: document.getElementById('campo-licencia').value.trim(),
        };
        try {
            let respuesta;
            if (this.#idEnEdicion === null) {
                respuesta = await this.#api.crear(datos);
            } else {
                respuesta = await this.#api.actualizar(this.#idEnEdicion, datos);
            }
            this.#feedback.mostrarExito(alertas, respuesta.message);
            document.getElementById('form-cliente').reset();
            this.#idEnEdicion = null;
            await this.#cargar();
        } catch (error) {
            this.#feedback.mostrarError(alertas, error);
        }
    }

    #cancelarEdicion() {
        this.#idEnEdicion = null;
    }

    #accionFila(evento) {
        const boton = evento.target.closest('button[data-accion]');
        if (!boton) {
            return;
        }
        const id = Number(boton.getAttribute('data-id'));
        if (boton.getAttribute('data-accion') === 'editar') {
            this.#editar(id);
        } else {
            this.#idPendiente = id;
            this.#feedback.pedirConfirmacion(
                document.getElementById('dialogo-confirmar'),
                document.getElementById('dialogo-mensaje'),
                '¿Eliminar el cliente #' + id + '?',
            );
        }
    }

    async #editar(id) {
        const alertas = document.querySelector('[data-alertas]');
        try {
            const respuesta = await this.#api.obtener(id);
            const cliente = respuesta.data;
            document.getElementById('cliente-id').value = cliente.id;
            document.getElementById('campo-nombre').value = cliente.nombre ?? '';
            document.getElementById('campo-telefono').value = cliente.telefono ?? '';
            document.getElementById('campo-correo').value = cliente.correo ?? '';
            document.getElementById('campo-licencia').value = cliente.numero_licencia ?? '';
            this.#idEnEdicion = cliente.id;
            this.#feedback.limpiar(alertas);
            document.getElementById('campo-nombre').focus();
        } catch (error) {
            this.#feedback.mostrarError(alertas, error);
        }
    }

    async #resolverConfirmacion(evento) {
        if (evento.target.returnValue !== 'aceptar' || this.#idPendiente === null) {
            this.#idPendiente = null;
            return;
        }
        const alertas = document.querySelector('[data-alertas]');
        const id = this.#idPendiente;
        this.#idPendiente = null;
        try {
            const respuesta = await this.#api.eliminar(id);
            this.#feedback.mostrarExito(alertas, respuesta.message);
            await this.#cargar();
        } catch (error) {
            this.#feedback.mostrarError(alertas, error);
        }
    }
}

const controlador = new ClientesPageController(
    new ClientesApi(new HttpClient(AppConfig.clientesUrl, AppConfig.token)),
    new UiFeedback(),
);
controlador.init();
