// Cliente del microservicio ms_vehiculos (:8001). Recibe HttpClient por DI.
export class VehiculosApi {
    #http;

    constructor(httpClient) {
        this.#http = httpClient;
    }

    listar(estado) {
        const ruta = estado ? '/vehiculos?estado=' + encodeURIComponent(estado) : '/vehiculos';
        return this.#http.get(ruta);
    }

    obtener(id) {
        return this.#http.get('/vehiculos/' + encodeURIComponent(id));
    }

    crear(datos) {
        return this.#http.post('/vehiculos', datos);
    }

    actualizar(id, datos) {
        return this.#http.put('/vehiculos/' + encodeURIComponent(id), datos);
    }

    cambiarEstado(id, estado) {
        return this.#http.patch('/vehiculos/' + encodeURIComponent(id) + '/estado', { estado });
    }

    eliminar(id) {
        return this.#http.delete('/vehiculos/' + encodeURIComponent(id));
    }
}
