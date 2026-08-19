# Monitor de sitios

Panel para ver si tus sitios web están activos o desconectados.

- **Laravel**: API, chequeos HTTP y scheduler
- **WhatsApp Cloud API**: aviso con plantillas cuando un sitio cae o vuelve
- **Astro + Vue**: dashboard con componentes

Este proyecto **no tiene webhook**. Solo envía plantillas. El webhook del mismo WABA está en POS:

`https://api-level.minegociolisto.com/api/webhooks/whatsapp` (`GET|POST /api/webhooks/whatsapp`).

## Cómo usarlo

```bash
cd ~/Documents/monitor-sitios
chmod +x start.sh
./start.sh
```

Luego abre [http://localhost:4321](http://localhost:4321).

La API Laravel queda en [http://127.0.0.1:8080](http://127.0.0.1:8080).

## MySQL (MAMP)

MAMP debe estar encendido. Al arrancar, Laravel crea sola la base `monitor_sitios` en `127.0.0.1:8889` y corre las migraciones.

Credenciales por defecto en `api/.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8889
DB_DATABASE=monitor_sitios
DB_USERNAME=root
DB_PASSWORD=root
```

## WhatsApp

Usa el mismo token de System User y Phone Number ID que el POS. En `api/.env`:

```
WHATSAPP_ENABLED=true
WHATSAPP_ACCESS_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_WABA_ID=
WHATSAPP_API_VERSION=v25.0
WHATSAPP_TEMPLATE_LANGUAGE=es
WHATSAPP_TEMPLATE_DOWN=monitor_sitio_caido
WHATSAPP_TEMPLATE_UP=monitor_sitio_activo
WHATSAPP_DEFAULT_COUNTRY_CODE=593
FAIL_THRESHOLD=3
SLOW_THRESHOLD_MS=3000
CHECK_INTERVAL_MINUTES=1
```

En el panel, cada sitio lleva país y número de WhatsApp. Ahí llegan las alertas.

Plantillas (Utility, aprobadas en Meta):

| Evento | Plantilla | Variables |
|--------|-----------|-----------|
| Caída | `monitor_sitio_caido` | nombre, URL, detalle, error técnico, tiempo |
| Recuperación | `monitor_sitio_activo` | nombre, URL, HTTP, tiempo |
| Resumen 6 h | `monitor_resumen` | nombre, estado, URL (un mensaje por sitio) |

El aviso de caída sale después de **3 fallos seguidos**.

Con el panel abierto, Laravel chequea solo según **Automático cada X min**. El cron sirve para chequear aunque cierres el dashboard. El scheduler corre cada minuto, pero respeta el intervalo que configuraste.

## Cron (chequeo con el panel cerrado)

```cron
* * * * * php /Users/tinguar/Documents/monitor-sitios/api/artisan schedule:run >> /tmp/monitor-sitios.log 2>&1
```

O ejecuta el chequeo a mano:

```bash
php api/artisan sites:check
```

## Producción

Panel y API en **subdominios distintos**:

- **API:** `https://api-run-test.tinguar.com` → document root `api/public`
- **Panel:** `https://web-run-test.tinguar.com` → document root `web/dist`

El build del panel usa `web/.env.production`:

```
PUBLIC_API_BASE=https://api-run-test.tinguar.com
```

En `api/.env` del servidor:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api-run-test.tinguar.com
CORS_ALLOWED_ORIGINS=https://web-run-test.tinguar.com
```

En el servidor (API):

```bash
cd api
composer install --no-dev --optimize-autoloader
php artisan key:generate   # solo la primera vez
php artisan migrate --force
php artisan admin:ensure
php artisan config:cache
php artisan route:cache
```

Panel:

```bash
cd web
npm ci
npm run build
```

Sube `web/dist` y `api/` (sin `node_modules`). Sí sube `api/vendor` o corre `composer install` en el servidor.

Cron obligatorio (si no, no hay chequeos ni resumen 6 h con el panel cerrado):

```cron
* * * * * php /RUTA/api/artisan schedule:run >> /tmp/monitor-sitios.log 2>&1
```

No subas `api/.env` de tu Mac: crea uno nuevo en el servidor con MySQL de Hostinger, `APP_DEBUG=false`, el token de WhatsApp y `ADMIN_EMAIL` / `ADMIN_PASSWORD`. Luego `php artisan admin:ensure`.

El panel pide inicio de sesión. La API (salvo `GET /api` y `POST /api/login`) exige `Authorization: Bearer`.

## Estructura

```
monitor-sitios/
├── api/                 Laravel (API + scheduler + MySQL/MAMP)
│   ├── app/Http/Controllers/Api
│   ├── app/Models
│   ├── app/Services     SiteChecker, WhatsAppCloudClient, WhatsAppNotifier, SiteMonitor
│   ├── routes/api.php
│   └── database/migrations
└── web/                 Astro + componentes Vue
    └── src/components/
        ├── SiteList.vue
        ├── SiteCard.vue
        ├── StatusBadge.vue
        ├── AlertBanner.vue
        ├── AddSiteForm.vue
        └── WhatsAppSettings.vue
```

## API

| Método | Ruta | Uso |
|--------|------|-----|
| GET | `/api/sites` | Lista de sitios y estado |
| POST | `/api/sites` | Agregar sitio `{ "name", "url" }` |
| DELETE | `/api/sites/{id}` | Quitar sitio |
| GET | `/api/sites/{id}/checks` | Historial |
| POST | `/api/checks/run` | Chequear todos ahora |
| GET/PUT | `/api/settings` | Umbrales |
