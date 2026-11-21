// Error de dominio para fallos HTTP: conserva el mensaje del contrato API y el estado.
export class ApiError extends Error {
    #estado;

    constructor(mensaje, estado) {
        super(mensaje);
        this.name = 'ApiError';
        this.#estado = estado;
    }

    get estado() {
        return this.#estado;
    }
}

// Cliente base de comunicación fetch(): solo gestiona HTTP y errores.
// Cada clase *Api lo recibe por inyección de dependencias (DIP).
export class HttpClient {
    #baseUrl;
    #token;

    constructor(baseUrl, token) {
        this.#baseUrl = baseUrl;
        this.#token = token;
    }

    get(ruta) {
        return this.#enviar('GET', ruta);
    }

    post(ruta, cuerpo) {
        return this.#enviar('POST', ruta, cuerpo);
    }

    put(ruta, cuerpo) {
        return this.#enviar('PUT', ruta, cuerpo);
    }

    patch(ruta, cuerpo) {
        return this.#enviar('PATCH', ruta, cuerpo);
    }

    delete(ruta) {
        return this.#enviar('DELETE', ruta);
    }

    async #enviar(metodo, ruta, cuerpo) {
        const cabeceras = { Authorization: this.#token };
        const opciones = { method: metodo, headers: cabeceras };

        if (cuerpo !== undefined) {
            cabeceras['Content-Type'] = 'application/json';
            opciones.body = JSON.stringify(cuerpo);
        }

        let respuesta;
        try {
            respuesta = await fetch(this.#baseUrl + ruta, opciones);
        } catch (falloRed) {
            throw new ApiError('No se pudo contactar el microservicio', 0);
        }

        const texto = await respuesta.text();
        let datos = null;
        if (texto !== '') {
            try {
                datos = JSON.parse(texto);
            } catch (invalido) {
                throw new ApiError('Respuesta no válida del microservicio', respuesta.status);
            }
        }

        if (!respuesta.ok) {
            const mensaje = datos && typeof datos.error === 'string' && datos.error !== ''
                ? datos.error
                : 'Operación rechazada por el microservicio';
            throw new ApiError(mensaje, respuesta.status);
        }

        return datos;
    }
}
