import { AppConfig } from '../config.js';
import { HttpClient } from '../core/HttpClient.js';
import { UiFeedback } from '../core/UiFeedback.js';
import { HistorialApi } from '../api/HistorialApi.js';

// Controla la pantalla de historial: reporte básico, filtros y
// detalle por vehículo o cliente. Recibe Api y UiFeedback por constructor.
export class HistorialPageController {
    #api;
    #feedback;

    constructor(historialApi, feedback) {
        this.#api = historialApi;
        this.#feedback = feedback;
    }

    async init() {
        document.getElementById('form-historial').addEventListener('submit', (evento) => this.#filtrar(evento));
        document.getElementById('form-historial').addEventListener('reset', () => this.#limpiarTabla());
        document.getElementById('form-vehiculo-h').addEventListener('submit', (evento) => this.#detalleVehiculo(evento));
        document.getElementById('form-cliente-h').addEventListener('submit', (evento) => this.#detalleCliente(evento));
        await Promise.all([this.#cargarResumen(), this.#cargarHistorial()]);
    }

    async #cargarResumen() {
        const alertas = document.querySelector('[data-alertas-resumen]');
        try {
            const respuesta = await this.#api.resumen();
            const datos = respuesta.data;
            this.#texto('[data-t-total]', datos.total_reservas);
            this.#texto('[data-t-activas]', datos.activas);
            this.#texto('[data-t-completadas]', datos.completadas);
            this.#texto('[data-t-canceladas]', datos.canceladas);
            this.#pintarUltimas(datos.ultimas_reservas || []);
        } catch (error) {
            this.#feedback.mostrarError(alertas, error);
        }
    }

    #pintarUltimas(ultimas) {
        const lista = document.querySelector('[data-ultimas]');
        lista.replaceChildren();
        for (const reserva of ultimas) {
            const elemento = document.createElement('li');
            elemento.classList.add('tarjeta');
            const titulo = document.createElement('h3');
            titulo.textContent = 'Reserva #' + reserva.id;
            const detalle = document.createElement('p');
            detalle.textContent = this.#nombreCliente(reserva) + ' — ' + this.#nombreVehiculo(reserva);
            elemento.append(titulo, detalle, this.#feedback.insignia(reserva.estado));
            lista.append(elemento);
        }
    }

    async #cargarHistorial() {
        await this.#filtrarDatos({
            estado: document.getElementById('filtro-h-estado').value,
            desde: document.getElementById('filtro-desde').value,
            hasta: document.getElementById('filtro-hasta').value,
        });
    }

    async #filtrar(evento) {
        evento.preventDefault();
        await this.#cargarHistorial();
    }

    async #filtrarDatos(filtros) {
        const alertas = document.querySelector('[data-alertas]');
        try {
            const respuesta = await this.#api.listar({
                estado: filtros.estado !== '' ? filtros.estado : null,
                desde: filtros.desde !== '' ? filtros.desde : null,
                hasta: filtros.hasta !== '' ? filtros.hasta : null,
            });
            this.#feedback.limpiar(alertas);
            this.#pintar(respuesta.data || []);
        } catch (error) {
            this.#feedback.mostrarError(alertas, error);
        }
    }

    #pintar(reservas) {
        const cuerpo = document.getElementById('tabla-historial');
        const vacio = document.getElementById('sin-historial');
        cuerpo.replaceChildren();
        vacio.hidden = reservas.length > 0;
        for (const reserva of reservas) {
            const fila = document.createElement('tr');
            fila.append(this.#celdaId(reserva.id));
            fila.append(this.#celdaTexto(this.#nombreCliente(reserva)));
            fila.append(this.#celdaTexto(this.#nombreVehiculo(reserva)));
            for (const clave of ['fecha_inicio', 'fecha_fin', 'fecha_devolucion_real']) {
                const celda = document.createElement('td');
                celda.append(this.#feedback.fecha(reserva[clave]));
                fila.append(celda);
            }
            const estado = document.createElement('td');
            estado.append(this.#feedback.insignia(reserva.estado));
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

    async #detalleVehiculo(evento) {
        evento.preventDefault();
        const alertas = document.querySelector('[data-alertas-detalle]');
        const id = Number(document.getElementById('campo-vehiculo-id').value);
        try {
            const respuesta = await this.#api.porVehiculo(id);
            this.#feedback.limpiar(alertas);
            const datos = respuesta.data;
            const vehiculo = datos.vehiculo;
            this.#pintarDetalle(
                'Vehículo #' + vehiculo.id + ' — ' + vehiculo.marca + ' ' + vehiculo.modelo,
                [['Total de alquileres', datos.total_alquileres]],
            );
        } catch (error) {
            this.#feedback.mostrarError(alertas, error);
        }
    }

    async #detalleCliente(evento) {
        evento.preventDefault();
        const alertas = document.querySelector('[data-alertas-detalle]');
        const id = Number(document.getElementById('campo-cliente-id').value);
        try {
            const respuesta = await this.#api.porCliente(id);
            this.#feedback.limpiar(alertas);
            const datos = respuesta.data;
            this.#pintarDetalle(
                'Cliente #' + datos.cliente.id + ' — ' + datos.cliente.nombre,
                [['Total de alquileres', datos.total_alquileres]],
            );
        } catch (error) {
            this.#feedback.mostrarError(alertas, error);
        }
    }

    #pintarDetalle(titulo, pares) {
        const encabezado = document.querySelector('[data-detalle-titulo]');
        const lista = document.querySelector('[data-detalle]');
        encabezado.textContent = titulo;
        encabezado.hidden = false;
        lista.replaceChildren();
        lista.hidden = false;
        for (const [termino, valor] of pares) {
            const dt = document.createElement('dt');
            dt.textContent = termino;
            const dd = document.createElement('dd');
            const salida = document.createElement('output');
            salida.textContent = valor;
            dd.append(salida);
            lista.append(dt, dd);
        }
    }

    #limpiarTabla() {
        document.getElementById('tabla-historial').replaceChildren();
        document.getElementById('sin-historial').hidden = true;
        this.#feedback.limpiar(document.querySelector('[data-alertas]'));
    }

    #texto(selector, valor) {
        const elemento = document.querySelector(selector);
        if (elemento) {
            elemento.textContent = valor;
        }
    }
}

const controlador = new HistorialPageController(
    new HistorialApi(new HttpClient(AppConfig.historialUrl, AppConfig.token)),
    new UiFeedback(),
);
controlador.init();
