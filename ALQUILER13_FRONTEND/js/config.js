// Configuración inmutable de la aplicación: URLs de los microservicios y token.
// Se expone como clase con accesores estáticos de solo lectura y se congela
// para impedir cualquier modificación en tiempo de ejecución.
export class AppConfig {
    static get token() {
        return 'Bearer 12345';
    }

    static get vehiculosUrl() {
        return 'http://127.0.0.1:8001';
    }

    static get clientesUrl() {
        return 'http://127.0.0.1:8002';
    }

    static get reservasUrl() {
        return 'http://127.0.0.1:8003';
    }

    static get devolucionesUrl() {
        return 'http://127.0.0.1:8004';
    }

    static get historialUrl() {
        return 'http://127.0.0.1:8005';
    }

    static get disponibilidadUrl() {
        return 'http://127.0.0.1:8006';
    }
}

Object.freeze(AppConfig);
