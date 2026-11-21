// Cliente del microservicio ms_historial (:8005). Recibe HttpClient por DI.
export class HistorialApi {
    #http;

    constructor(httpClient) {
        this.#http = httpClient;
    }

    listar(filtros) {
        const params = new URLSearchParams();
        if (filtros && filtros.estado) {
            params.append('estado', filtros.estado);
        }
        if (filtros && filtros.desde) {
            params.append('desde', filtros.desde);
        }
        if (filtros && filtros.hasta) {
            params.append('hasta', filtros.hasta);
        }
        const query = params.toString();
        return this.#http.get(query ? '/historial?' + query : '/historial');
    }

    porVehiculo(id) {
        return this.#http.get('/historial/vehiculo/' + encodeURIComponent(id));
    }

    porCliente(id) {
        return this.#http.get('/historial/cliente/' + encodeURIComponent(id));
    }

    resumen() {
        return this.#http.get('/historial/resumen');
    }
}
