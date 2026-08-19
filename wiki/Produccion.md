# Producción

Panel y API suelen ir en **subdominios distintos**.

Ejemplo:

| Rol | Dominio | Document root |
|-----|---------|----------------|
| API | `https://api.tudominio.com` | `api/public` |
| Panel | `https://panel.tudominio.com` | `web/dist` |

## Panel

En `web/.env.production`:

```
PUBLIC_API_BASE=https://api.tudominio.com
```

```bash
cd web
npm ci
npm run build
```

Sube **`web/dist`**.

## API

No subas el `.env` de tu computador. Crea uno en el servidor:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.tudominio.com
CORS_ALLOWED_ORIGINS=https://panel.tudominio.com
```

Más MySQL del hosting, `APP_KEY`, token de WhatsApp, `ADMIN_EMAIL` y `ADMIN_PASSWORD`.

```bash
cd api
composer install --no-dev --optimize-autoloader
php artisan key:generate   # solo la primera vez
php artisan migrate --force
php artisan admin:ensure
php artisan config:cache
php artisan route:cache
```

Si al correr artisan aparece `PailServiceProvider not found`, borra caché de paquetes de desarrollo:

```bash
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php
php artisan package:discover
```

**No subas** `bootstrap/cache/packages.php` ni `services.php` desde tu Mac.

## Cron (obligatorio)

Sin esto no hay chequeos ni resumen con el panel cerrado:

```cron
* * * * * php /RUTA/api/artisan schedule:run >> /tmp/monitor-sitios.log 2>&1
```

## Login

El panel pide sesión. La API (salvo `GET /api` y `POST /api/login`) exige `Authorization: Bearer`.
