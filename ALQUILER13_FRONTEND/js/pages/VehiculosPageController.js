import { AppConfig } from '../config.js';
import { HttpClient } from '../core/HttpClient.js';
import { UiFeedback } from '../core/UiFeedback.js';
import { VehiculosApi } from '../api/VehiculosApi.js';

// Controla la pantalla de vehículos: listado, registro, edición,
// cambio de estado y eliminación. Recibe Api y UiFeedback por constructor.
export class VehiculosPageController {
    #api;
    #feedback;
    #idEnEdicion;
    #pendiente;

    constructor(vehiculosApi, feedback) {
        this.#api = vehiculosApi;
        this.#feedback = feedback;
        this.#idEnEdicion = null;
        this.#pendiente = null;
    }

    init() {
        const formulario = document.getElementById('form-vehiculo');
        formulario.addEventListener('submit', (evento) => this.#guardar(evento));
        formulario.addEventListener('reset', () => this.#cancelarEdicion());
        document.getElementById('filtro-estado').addEventListener('change', () => this.#cargar());
        document.getElementById('tabla-vehiculos').addEventListener('click', (evento) => this.#accionFila(evento));
        document.getElementById('dialogo-confirmar').addEventListener('close', (evento) => this.#resolverConfirmacion(evento));
        this.#cargar();
    }

    async #cargar() {
        const alertas = document.querySelector('[data-alertas]');
        const filtro = document.getElementById('filtro-estado').value;
        try {
            const respuesta = await this.#api.listar(filtro !== '' ? filtro : null);
            this.#feedback.limpiar(alertas);
            this.#pintar(respuesta.data || []);
        } catch (error) {
            this.#feedback.mostrarError(alertas, error);
        }
    }

    #pintar(vehiculos) {
        const cuerpo = document.getElementById('tabla-vehiculos');
        const vacio = document.getElementById('sin-registros');
        cuerpo.replaceChildren();
        vacio.hidden = vehiculos.length > 0;
        for (const vehiculo of vehiculos) {
            const fila = document.createElement('tr');
            fila.append(this.#celdaId(vehiculo.id));
            for (const clave of ['marca', 'modelo', 'anio', 'categoria']) {
                const celda = document.createElement('td');
                celda.textContent = vehiculo[clave] ?? '—';
                fila.append(celda);
            }
            const estado = document.createElement('td');
            estado.append(this.#feedback.insignia(vehiculo.estado));
            fila.append(estado);
            fila.append(this.#celdaAcciones(vehiculo));
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

    #celdaAcciones(vehiculo) {
        const celda = document.createElement('td');
        const editar = this.#boton('Editar', 'editar', vehiculo.id, 'boton-secundario');
        const eliminar = this.#boton('Eliminar', 'eliminar', vehiculo.id, 'boton-peligro');
        celda.append(editar, eliminar);
        if (vehiculo.estado !== 'alquilado') {
            const siguiente = vehiculo.estado === 'disponible' ? 'mantenimiento' : 'disponible';
            const etiqueta = vehiculo.estado === 'disponible' ? 'A mantenimiento' : 'Activar';
            celda.append(this.#boton(etiqueta, 'estado:' + siguiente, vehiculo.id, 'boton-secundario'));
        }
        return celda;
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
            marca: document.getElementById('campo-marca').value.trim(),
            modelo: document.getElementById('campo-modelo').value.trim(),
            anio: Number(document.getElementById('campo-anio').value),
            categoria: document.getElementById('campo-categoria').value.trim(),
        };
        try {
            let respuesta;
            if (this.#idEnEdicion === null) {
                datos.estado = document.getElementById('campo-estado').value;
                respuesta = await this.#api.crear(datos);
            } else {
                respuesta = await this.#api.actualizar(this.#idEnEdicion, datos);
            }
            this.#feedback.mostrarExito(alertas, respuesta.message);
            document.getElementById('form-vehiculo').reset();
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
        const accion = boton.getAttribute('data-accion');
        if (accion === 'editar') {
            this.#editar(id);
        } else if (accion === 'eliminar') {
            this.#pendiente = { tipo: 'eliminar', id };
            this.#feedback.pedirConfirmacion(
                document.getElementById('dialogo-confirmar'),
                document.getElementById('dialogo-mensaje'),
                '¿Eliminar el vehículo #' + id + '?',
            );
        } else if (accion.startsWith('estado:')) {
            this.#pendiente = { tipo: 'estado', id, estado: accion.slice(7) };
            this.#feedback.pedirConfirmacion(
                document.getElementById('dialogo-confirmar'),
                document.getElementById('dialogo-mensaje'),
                '¿Cambiar el vehículo #' + id + ' a "' + accion.slice(7) + '"?',
            );
        }
    }

    async #editar(id) {
        const alertas = document.querySelector('[data-alertas]');
        try {
            const respuesta = await this.#api.obtener(id);
            const vehiculo = respuesta.data;
            document.getElementById('vehiculo-id').value = vehiculo.id;
            document.getElementById('campo-marca').value = vehiculo.marca ?? '';
            document.getElementById('campo-modelo').value = vehiculo.modelo ?? '';
            document.getElementById('campo-anio').value = vehiculo.anio ?? '';
            document.getElementById('campo-categoria').value = vehiculo.categoria ?? '';
            document.getElementById('campo-estado').value = vehiculo.estado;
            this.#idEnEdicion = vehiculo.id;
            this.#feedback.limpiar(alertas);
            document.getElementById('campo-marca').focus();
        } catch (error) {
            this.#feedback.mostrarError(alertas, error);
        }
    }

    async #resolverConfirmacion(evento) {
        if (evento.target.returnValue !== 'aceptar' || !this.#pendiente) {
            this.#pendiente = null;
            return;
        }
        const alertas = document.querySelector('[data-alertas]');
        const tarea = this.#pendiente;
        this.#pendiente = null;
        try {
            let respuesta;
            if (tarea.tipo === 'eliminar') {
                respuesta = await this.#api.eliminar(tarea.id);
            } else {
                respuesta = await this.#api.cambiarEstado(tarea.id, tarea.estado);
            }
            this.#feedback.mostrarExito(alertas, respuesta.message);
            await this.#cargar();
        } catch (error) {
            this.#feedback.mostrarError(alertas, error);
        }
    }
}

const controlador = new VehiculosPageController(
    new VehiculosApi(new HttpClient(AppConfig.vehiculosUrl, AppConfig.token)),
    new UiFeedback(),
);
controlador.init();
