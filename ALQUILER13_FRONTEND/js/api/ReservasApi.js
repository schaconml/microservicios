// Cliente del microservicio ms_reservas (:8003). Recibe HttpClient por DI.
export class ReservasApi {
    #http;

    constructor(httpClient) {
        this.#http = httpClient;
    }

    listar(estado) {
        const ruta = estado ? '/reservas?estado=' + encodeURIComponent(estado) : '/reservas';
        return this.#http.get(ruta);
    }

    obtener(id) {
        return this.#http.get('/reservas/' + encodeURIComponent(id));
    }

    crear(datos) {
        return this.#http.post('/reservas', datos);
    }

    cancelar(id) {
        return this.#http.patch('/reservas/' + encodeURIComponent(id) + '/cancelar');
    }
}
