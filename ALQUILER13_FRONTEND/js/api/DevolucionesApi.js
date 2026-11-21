// Cliente del microservicio ms_devoluciones (:8004). Recibe HttpClient por DI.
export class DevolucionesApi {
    #http;

    constructor(httpClient) {
        this.#http = httpClient;
    }

    listar() {
        return this.#http.get('/devoluciones');
    }

    pendientes() {
        return this.#http.get('/devoluciones/pendientes');
    }

    registrar(datos) {
        return this.#http.post('/devoluciones', datos);
    }
}
