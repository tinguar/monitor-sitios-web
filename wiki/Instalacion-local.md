# Instalación local

## Requisitos

- PHP 8.3+
- Composer
- Node.js 20+
- MySQL (en desarrollo se usó MAMP, puerto `8889`)

## Arranque

```bash
git clone https://github.com/tinguar/monitor-sitios-web.git
cd monitor-sitios-web
cp api/.env.example api/.env
# Edita api/.env (APP_KEY, MySQL, WhatsApp)
cd api && composer install && php artisan key:generate
chmod +x start.sh
./start.sh
```

- Panel: http://localhost:4321  
- API: http://127.0.0.1:8080  

## MySQL

Laravel puede crear la base `monitor_sitios` al arrancar (`php artisan monitor:prepare` / `start.sh`).

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8889
DB_DATABASE=monitor_sitios
DB_USERNAME=root
DB_PASSWORD=root
```

Luego:

```bash
cd api
php artisan migrate
php artisan admin:ensure
```

Define en `.env` `ADMIN_EMAIL` y `ADMIN_PASSWORD` (mínimo 12 caracteres) **antes** de `admin:ensure`.

## Cron local (opcional)

Con `./start.sh` ya corre `php artisan schedule:work`. Si no:

```cron
* * * * * php /RUTA/monitor-sitios-web/api/artisan schedule:run >> /tmp/monitor-sitios.log 2>&1
```
