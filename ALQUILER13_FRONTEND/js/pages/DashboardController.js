import { AppConfig } from '../config.js';
import { HttpClient } from '../core/HttpClient.js';
import { UiFeedback } from '../core/UiFeedback.js';
import { DisponibilidadApi } from '../api/DisponibilidadApi.js';
import { HistorialApi } from '../api/HistorialApi.js';

// Controla el panel principal: resúmenes de flota e historial y consulta rápida.
// Recibe sus dependencias por constructor (DIP) y usa addEventListener (SRP).
export class DashboardController {
    #disponibilidadApi;
    #historialApi;
    #feedback;

    constructor(disponibilidadApi, historialApi, feedback) {
        this.#disponibilidadApi = disponibilidadApi;
        this.#historialApi = historialApi;
        this.#feedback = feedback;
    }

    async init() {
        const formulario = document.getElementById('form-disponibilidad');
        if (formulario) {
            formulario.addEventListener('submit', (evento) => this.#consultar(evento));
            formulario.addEventListener('reset', () => this.#limpiarResultados());
        }
        await Promise.all([this.#cargarFlota(), this.#cargarHistorial()]);
    }

    async #cargarFlota() {
        const alertas = document.querySelector('[data-alertas-flota]');
        try {
            const respuesta = await this.#disponibilidadApi.resumen();
            const datos = respuesta.data;
            this.#texto('[data-flota-total]', datos.total_vehiculos);
            this.#texto('[data-flota-disponibles]', datos.disponibles);
            this.#texto('[data-flota-alquilados]', datos.alquilados);
            this.#texto('[data-flota-mantenimiento]', datos.mantenimiento);
            this.#pintarCategorias(datos.disponibles_por_categoria || []);
        } catch (error) {
            this.#feedback.mostrarError(alertas, error);
        }
    }

    async #cargarHistorial() {
        const alertas = document.querySelector('[data-alertas-historial]');
        try {
            const respuesta = await this.#historialApi.resumen();
            const datos = respuesta.data;
            this.#texto('[data-h-total]', datos.total_reservas);
            this.#texto('[data-h-activas]', datos.activas);
            this.#texto('[data-h-completadas]', datos.completadas);
            this.#texto('[data-h-canceladas]', datos.canceladas);
        } catch (error) {
            this.#feedback.mostrarError(alertas, error);
        }
    }

    async #consultar(evento) {
        evento.preventDefault();
        const alertas = document.querySelector('[data-alertas]');
        const categoria = document.getElementById('campo-categoria').value.trim();
        const desde = document.getElementById('campo-desde').value;
        const hasta = document.getElementById('campo-hasta').value;
        try {
            const respuesta = await this.#disponibilidadApi.consultar({
                categoria: categoria !== '' ? categoria : null,
                fechaInicio: desde !== '' ? desde : null,
                fechaFin: hasta !== '' ? hasta : null,
            });
            this.#feedback.limpiar(alertas);
            this.#pintarResultados(respuesta.data || []);
        } catch (error) {
            this.#feedback.mostrarError(alertas, error);
        }
    }

    #pintarResultados(vehiculos) {
        const cuerpo = document.getElementById('tabla-resultados');
        const vacio = document.getElementById('sin-resultados');
        cuerpo.replaceChildren();
        vacio.hidden = vehiculos.length > 0;
        for (const vehiculo of vehiculos) {
            const fila = document.createElement('tr');
            const identificador = document.createElement('th');
            identificador.setAttribute('scope', 'row');
            const dato = document.createElement('data');
            dato.value = vehiculo.id;
            dato.textContent = '#' + vehiculo.id;
            identificador.append(dato);
            fila.append(identificador);
            for (const clave of ['marca', 'modelo', 'anio', 'categoria']) {
                const celda = document.createElement('td');
                celda.textContent = vehiculo[clave] ?? '—';
                fila.append(celda);
            }
            const estado = document.createElement('td');
            estado.append(this.#feedback.insignia(vehiculo.estado));
            fila.append(estado);
            cuerpo.append(fila);
        }
    }

    #pintarCategorias(categorias) {
        const lista = document.querySelector('[data-categorias]');
        lista.replaceChildren();
        for (const item of categorias) {
            const elemento = document.createElement('li');
            elemento.classList.add('tarjeta');
            const titulo = document.createElement('h3');
            titulo.textContent = item.categoria ?? 'Sin categoría';
            const total = document.createElement('output');
            total.textContent = item.total;
            elemento.append(titulo, total);
            lista.append(elemento);
        }
    }

    #limpiarResultados() {
        document.getElementById('tabla-resultados').replaceChildren();
        document.getElementById('sin-resultados').hidden = true;
        this.#feedback.limpiar(document.querySelector('[data-alertas]'));
    }

    #texto(selector, valor) {
        const elemento = document.querySelector(selector);
        if (elemento) {
            elemento.textContent = valor;
        }
    }
}

const controlador = new DashboardController(
    new DisponibilidadApi(new HttpClient(AppConfig.disponibilidadUrl, AppConfig.token)),
    new HistorialApi(new HttpClient(AppConfig.historialUrl, AppConfig.token)),
    new UiFeedback(),
);
controlador.init();
