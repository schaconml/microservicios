import { AppConfig } from '../config.js';
import { HttpClient } from '../core/HttpClient.js';
import { UiFeedback } from '../core/UiFeedback.js';
import { DevolucionesApi } from '../api/DevolucionesApi.js';

// Controla la pantalla de devoluciones: pendientes, registro y completadas.
// Recibe Api y UiFeedback por constructor.
export class DevolucionesPageController {
    #api;
    #feedback;

    constructor(devolucionesApi, feedback) {
        this.#api = devolucionesApi;
        this.#feedback = feedback;
    }

    init() {
        document.getElementById('form-devolucion').addEventListener('submit', (evento) => this.#registrar(evento));
        document.getElementById('tabla-pendientes').addEventListener('click', (evento) => this.#usarPendiente(evento));
        this.#cargarTodo();
    }

    async #cargarTodo() {
        const alertas = document.querySelector('[data-alertas]');
        try {
            const [pendientes, completadas] = await Promise.all([
                this.#api.pendientes(),
                this.#api.listar(),
            ]);
            this.#feedback.limpiar(alertas);
            this.#pintarPendientes(pendientes.data || []);
            this.#pintarCompletadas(completadas.data || []);
        } catch (error) {
            this.#feedback.mostrarError(alertas, error);
        }
    }

    #pintarPendientes(pendientes) {
        const cuerpo = document.getElementById('tabla-pendientes');
        const vacio = document.getElementById('sin-pendientes');
        cuerpo.replaceChildren();
        vacio.hidden = pendientes.length > 0;
        const selector = document.getElementById('campo-reserva');
        selector.replaceChildren();
        const inicial = document.createElement('option');
        inicial.value = '';
        inicial.textContent = 'Seleccione…';
        selector.append(inicial);
        for (const reserva of pendientes) {
            const fila = document.createElement('tr');
            fila.append(this.#celdaId(reserva.id));
            fila.append(this.#celdaTexto(this.#nombreCliente(reserva)));
            fila.append(this.#celdaTexto(this.#nombreVehiculo(reserva)));
            const fin = document.createElement('td');
            fin.append(this.#feedback.fecha(reserva.fecha_fin));
            fila.append(fin);
            const acciones = document.createElement('td');
            const usar = document.createElement('button');
            usar.type = 'button';
            usar.textContent = 'Usar';
            usar.classList.add('boton-secundario');
            usar.setAttribute('data-id', reserva.id);
            acciones.append(usar);
            fila.append(acciones);
            cuerpo.append(fila);
            const opcion = document.createElement('option');
            opcion.value = reserva.id;
            opcion.textContent = '#' + reserva.id + ' — ' + this.#nombreCliente(reserva) + ' / ' + this.#nombreVehiculo(reserva);
            selector.append(opcion);
        }
    }

    #pintarCompletadas(devoluciones) {
        const cuerpo = document.getElementById('tabla-devoluciones');
        const vacio = document.getElementById('sin-registros');
        cuerpo.replaceChildren();
        vacio.hidden = devoluciones.length > 0;
        for (const reserva of devoluciones) {
            const fila = document.createElement('tr');
            fila.append(this.#celdaId(reserva.id));
            fila.append(this.#celdaTexto(this.#nombreCliente(reserva)));
            fila.append(this.#celdaTexto(this.#nombreVehiculo(reserva)));
            const real = document.createElement('td');
            real.append(this.#feedback.fecha(reserva.fecha_devolucion_real));
            fila.append(real);
            const estado = document.createElement('td');
            const vehiculo = reserva.vehiculo;
            estado.append(this.#feedback.insignia(vehiculo && vehiculo.estado ? vehiculo.estado : 'disponible'));
            fila.append(estado);
            cuerpo.append(fila);
        }
    }

    #celdaId(id) {
        const celda = document.createElement('th');
        celda.setAttribute('scope', 'row');
        const dato = document.createElement('data');
        dato.value = id;
        dato.textContent = '#' + id;
        celda.append(dato);
        return celda;
    }

    #celdaTexto(texto) {
        const celda = document.createElement('td');
        celda.textContent = texto;
        return celda;
    }

    #nombreCliente(reserva) {
        if (reserva.cliente && reserva.cliente.nombre) {
            return reserva.cliente.nombre;
        }
        return 'Cliente #' + reserva.cliente_id;
    }

    #nombreVehiculo(reserva) {
        if (reserva.vehiculo && reserva.vehiculo.marca) {
            return reserva.vehiculo.marca + ' ' + reserva.vehiculo.modelo;
        }
        return 'Vehículo #' + reserva.vehiculo_id;
    }

    #usarPendiente(evento) {
        const boton = evento.target.closest('button[data-id]');
        if (!boton) {
            return;
        }
        document.getElementById('campo-reserva').value = boton.getAttribute('data-id');
        document.getElementById('campo-fecha').focus();
    }

    async #registrar(evento) {
        evento.preventDefault();
        const alertas = document.querySelector('[data-alertas]');
        const fecha = document.getElementById('campo-fecha').value;
        const datos = {
            reserva_id: Number(document.getElementById('campo-reserva').value),
            estado_vehiculo: document.getElementById('campo-estado-vehiculo').value,
        };
        if (fecha !== '') {
            datos.fecha_devolucion_real = fecha;
        }
        try {
            const respuesta = await this.#api.registrar(datos);
            this.#feedback.mostrarExito(alertas, respuesta.message);
            document.getElementById('form-devolucion').reset();
            await this.#cargarTodo();
        } catch (error) {
            this.#feedback.mostrarError(alertas, error);
        }
    }
}

const controlador = new DevolucionesPageController(
    new DevolucionesApi(new HttpClient(AppConfig.devolucionesUrl, AppConfig.token)),
    new UiFeedback(),
);
controlador.init();
