// Cliente del microservicio ms_disponibilidad (:8006). Recibe HttpClient por DI.
export class DisponibilidadApi {
    #http;

    constructor(httpClient) {
        this.#http = httpClient;
    }

    consultar(filtros) {
        const params = new URLSearchParams();
        if (filtros && filtros.categoria) {
            params.append('categoria', filtros.categoria);
        }
        if (filtros && filtros.fechaInicio) {
            params.append('fecha_inicio', filtros.fechaInicio);
        }
        if (filtros && filtros.fechaFin) {
            params.append('fecha_fin', filtros.fechaFin);
        }
        const query = params.toString();
        return this.#http.get(query ? '/disponibilidad?' + query : '/disponibilidad');
    }

    resumen() {
        return this.#http.get('/disponibilidad/resumen');
    }
}
