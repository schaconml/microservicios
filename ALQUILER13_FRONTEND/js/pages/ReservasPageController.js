import { AppConfig } from '../config.js';
import { HttpClient } from '../core/HttpClient.js';
import { UiFeedback } from '../core/UiFeedback.js';
import { ReservasApi } from '../api/ReservasApi.js';
import { ClientesApi } from '../api/ClientesApi.js';
import { VehiculosApi } from '../api/VehiculosApi.js';

// Controla la pantalla de reservas: creación, listado con filtro y
// cancelación de reservas activas. Recibe sus Apis por constructor.
export class ReservasPageController {
    #reservasApi;
    #clientesApi;
    #vehiculosApi;
    #feedback;
    #idPendiente;

    constructor(reservasApi, clientesApi, vehiculosApi, feedback) {
        this.#reservasApi = reservasApi;
        this.#clientesApi = clientesApi;
        this.#vehiculosApi = vehiculosApi;
        this.#feedback = feedback;
        this.#idPendiente = null;
    }

    async init() {
        const formulario = document.getElementById('form-reserva');
        formulario.addEventListener('submit', (evento) => this.#crear(evento));
        document.getElementById('filtro-estado').addEventListener('change', () => this.#cargar());
        document.getElementById('tabla-reservas').addEventListener('click', (evento) => this.#accionFila(evento));
        document.getElementById('dialogo-confirmar').addEventListener('close', (evento) => this.#resolverConfirmacion(evento));
        await Promise.all([this.#cargarSelectores(), this.#cargar()]);
    }

    async #cargarSelectores() {
        const alertas = document.querySelector('[data-alertas]');
        try {
            const [clientes, vehiculos] = await Promise.all([
                this.#clientesApi.listar(),
                this.#vehiculosApi.listar('disponible'),
            ]);
            this.#llenarSelector(document.getElementById('campo-cliente'), clientes.data || [], (cliente) => ({
                valor: cliente.id,
                texto: '#' + cliente.id + ' — ' + cliente.nombre,
            }));
            this.#llenarSelector(document.getElementById('campo-vehiculo'), vehiculos.data || [], (vehiculo) => ({
                valor: vehiculo.id,
                texto: '#' + vehiculo.id + ' — ' + vehiculo.marca + ' ' + vehiculo.modelo,
            }));
        } catch (error) {
            this.#feedback.mostrarError(alertas, error);
        }
    }

    #llenarSelector(selector, elementos, mapear) {
        selector.replaceChildren();
        const inicial = document.createElement('option');
        inicial.value = '';
        inicial.textContent = 'Seleccione…';
        selector.append(inicial);
        for (const elemento of elementos) {
            const { valor, texto } = mapear(elemento);
            const opcion = document.createElement('option');
            opcion.value = valor;
            opcion.textContent = texto;
            selector.append(opcion);
        }
    }

    async #cargar() {
        const alertas = document.querySelector('[data-alertas]');
        const filtro = document.getElementById('filtro-estado').value;
        try {
            const respuesta = await this.#reservasApi.listar(filtro !== '' ? filtro : null);
            this.#feedback.limpiar(alertas);
            this.#pintar(respuesta.data || []);
        } catch (error) {
            this.#feedback.mostrarError(alertas, error);
        }
    }

    #pintar(reservas) {
        const cuerpo = document.getElementById('tabla-reservas');
        const vacio = document.getElementById('sin-registros');
        cuerpo.replaceChildren();
        vacio.hidden = reservas.length > 0;
        for (const reserva of reservas) {
            const fila = document.createElement('tr');
            fila.append(this.#celdaId(reserva.id));
            fila.append(this.#celdaTexto(this.#nombreCliente(reserva)));
            fila.append(this.#celdaTexto(this.#nombreVehiculo(reserva)));
            const inicio = document.createElement('td');
            inicio.append(this.#feedback.fecha(reserva.fecha_inicio));
            const fin = document.createElement('td');
            fin.append(this.#feedback.fecha(reserva.fecha_fin));
            const real = document.createElement('td');
            real.append(this.#feedback.fecha(reserva.fecha_devolucion_real));
            fila.append(inicio, fin, real);
            const estado = document.createElement('td');
            estado.append(this.#feedback.insignia(reserva.estado));
            fila.append(estado);
            const acciones = document.createElement('td');
            if (reserva.estado === 'activa') {
                acciones.append(this.#botonCancelar(reserva.id));
            } else {
                acciones.append(this.#celdaTexto('—'));
            }
            fila.append(acciones);
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

    #botonCancelar(id) {
        const boton = document.createElement('button');
        boton.type = 'button';
        boton.textContent = 'Cancelar';
        boton.classList.add('boton-peligro');
        boton.setAttribute('data-accion', 'cancelar');
        boton.setAttribute('data-id', id);
        return boton;
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

    async #crear(evento) {
        evento.preventDefault();
        const alertas = document.querySelector('[data-alertas]');
        const datos = {
            cliente_id: Number(document.getElementById('campo-cliente').value),
            vehiculo_id: Number(document.getElementById('campo-vehiculo').value),
            fecha_inicio: document.getElementById('campo-inicio').value,
            fecha_fin: document.getElementById('campo-fin').value,
        };
        try {
            const respuesta = await this.#reservasApi.crear(datos);
            this.#feedback.mostrarExito(alertas, respuesta.message);
            document.getElementById('form-reserva').reset();
            await Promise.all([this.#cargarSelectores(), this.#cargar()]);
        } catch (error) {
            this.#feedback.mostrarError(alertas, error);
        }
    }

    #accionFila(evento) {
        const boton = evento.target.closest('button[data-accion="cancelar"]');
        if (!boton) {
            return;
        }
        this.#idPendiente = Number(boton.getAttribute('data-id'));
        this.#feedback.pedirConfirmacion(
            document.getElementById('dialogo-confirmar'),
            document.getElementById('dialogo-mensaje'),
            '¿Cancelar la reserva #' + this.#idPendiente + '? El vehículo volverá a disponible.',
        );
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
            const respuesta = await this.#reservasApi.cancelar(id);
            this.#feedback.mostrarExito(alertas, respuesta.message);
            await Promise.all([this.#cargarSelectores(), this.#cargar()]);
        } catch (error) {
            this.#feedback.mostrarError(alertas, error);
        }
    }
}

const controlador = new ReservasPageController(
    new ReservasApi(new HttpClient(AppConfig.reservasUrl, AppConfig.token)),
    new ClientesApi(new HttpClient(AppConfig.clientesUrl, AppConfig.token)),
    new VehiculosApi(new HttpClient(AppConfig.vehiculosUrl, AppConfig.token)),
    new UiFeedback(),
);
controlador.init();
