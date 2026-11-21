// Cliente del microservicio ms_clientes (:8002). Recibe HttpClient por DI.
export class ClientesApi {
    #http;

    constructor(httpClient) {
        this.#http = httpClient;
    }

    listar() {
        return this.#http.get('/clientes');
    }

    obtener(id) {
        return this.#http.get('/clientes/' + encodeURIComponent(id));
    }

    crear(datos) {
        return this.#http.post('/clientes', datos);
    }

    actualizar(id, datos) {
        return this.#http.put('/clientes/' + encodeURIComponent(id), datos);
    }

    eliminar(id) {
        return this.#http.delete('/clientes/' + encodeURIComponent(id));
    }
}
