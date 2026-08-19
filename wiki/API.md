# API

Prefijo Laravel: `/api`. En producción, CORS debe incluir el origen del panel.

Rutas públicas:

| Método | Ruta |
|--------|------|
| GET | `/api` (salud) |
| POST | `/api/login` `{ "email", "password" }` |

El resto exige cabecera `Authorization: Bearer <token>` (el token dura 8 horas).

| Método | Ruta | Uso |
|--------|------|-----|
| GET | `/api/me` | Usuario de la sesión |
| POST | `/api/logout` | Invalida el token |
| GET | `/api/sites` | Lista y estado |
| POST | `/api/sites` | Alta: `name`, `url`, `country_code`, `phone` |
| DELETE | `/api/sites/{id}` | Quitar |
| GET | `/api/sites/{id}/checks` | Historial |
| POST | `/api/checks/run` | Chequear todos ahora |
| GET / PUT | `/api/settings` | Umbrales y resumen 6 h |
| POST | `/api/whatsapp/test` | Prueba de plantilla: `template` (`down` \| `up` \| `digest`), `country_code`, `phone` |

Comandos:

```bash
php artisan sites:check
php artisan sites:digest --force
php artisan admin:ensure
```
