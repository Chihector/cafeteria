# Despliegue en producción - Café Rosé

Guía para montar el sistema en un servidor Linux (Ubuntu 24.04 LTS) con **MySQL**, **Nginx + HTTPS**, **Reverb supervisado con systemd**, **worker de colas** y **backups automáticos**.

> Los archivos de esta carpeta son plantillas. Reemplaza `cafeteria.example.com`, `ws.cafeteria.example.com` y las claves antes de usarlos.

> **Antes de desplegar:** confirma que el workflow de **CI** está en verde en la pestaña **Actions** del repositorio y revisa la hoja de ruta en [`../ROADMAP.md`](../ROADMAP.md). El CI no despliega nada: solo verifica pruebas, formato y compilación.

## Arquitectura

```
Navegador / POS / KDS
        │  HTTPS 443
        ▼
      Nginx ──────────────► PHP-FPM (Laravel)
        │                        │
        │  wss 443               ├─ MySQL 8
        ▼                        ├─ Reverb :8080 (WebSocket)
 ws.cafeteria.example.com        └─ Impresora térmica :9100 (LAN)
```

## 1. Requisitos del servidor

- Ubuntu 24.04 LTS (2 vCPU / 4 GB RAM recomendado).
- PHP 8.3 con extensiones: `mysql`, `mbstring`, `xml`, `curl`, `zip`, `bcmath`, `intl`, `gd`.
- MySQL 8 o MariaDB 10.11+.
- Nginx, Composer 2, Node.js 22 (solo para compilar assets).
- Certbot para HTTPS.
- La impresora térmica debe estar en la misma red que el servidor (puerto 9100).

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server unzip git curl \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl php8.3-gd \
  certbot python3-certbot-nginx

# Node.js 22
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs

# Composer 2
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

## 2. Base de datos MySQL

```bash
sudo mysql
```

```sql
CREATE DATABASE cafeteria CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cafeteria'@'127.0.0.1' IDENTIFIED BY 'UNA_CLAVE_LARGA_Y_UNICA';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP,
      REFERENCES, CREATE TEMPORARY TABLES, LOCK TABLES
  ON cafeteria.* TO 'cafeteria'@'127.0.0.1';
FLUSH PRIVILEGES;
EXIT;
```

## 3. Verificación de calidad antes del despliegue (25 nov 2026)

Antes de desplegar, verificar en el servidor de desarrollo o localmente que todo esté verde:

```bash
php artisan test          # suite: 65 pruebas / 324 aserciones, 0 fallos
vendor/bin/pint --test    # estilo aprobado
npm run build             # compilación de assets
php artisan route:list    # carga de rutas sin errores
composer validate         # composer.json válido
```

## 4. Instalar la aplicación

```bash
sudo mkdir -p /var/www/cafeteria
sudo chown "$USER":www-data /var/www/cafeteria
git clone https://github.com/chicohector953/cafeteria.git /var/www/cafeteria
cd /var/www/cafeteria

cp .env.production.example .env
nano .env
```

En `.env` cambia como mínimo:

- `APP_URL` y `APP_KEY` (se genera en el paso siguiente).
- `DB_PASSWORD`.
- `REVERB_APP_ID`, `REVERB_APP_KEY` y `REVERB_APP_SECRET` (valores únicos).
- `REVERB_HOST=ws.tudominio.com`.
- `PRINTER_*` si vas a usar impresora de red.

Generar la clave y credenciales de Reverb:

```bash
php artisan key:generate
php -r "echo 'REVERB_APP_ID='.random_int(100000, 999999).PHP_EOL;"
php -r "echo 'REVERB_APP_KEY='.bin2hex(random_bytes(16)).PHP_EOL;"
php -r "echo 'REVERB_APP_SECRET='.bin2hex(random_bytes(32)).PHP_EOL;"
```

Instalar dependencias y compilar:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build

php artisan migrate --force
php artisan storage:link
php artisan optimize
```

> **No ejecutes `migrate:fresh --seed` en producción.** El seeder crea usuarios y datos de demostración.

Crea el administrador real:

```bash
php artisan tinker
```

```php
\App\Models\User::create([
    'name' => 'Administrador',
    'email' => 'admin@tudominio.com',
    'password' => bcrypt('UNA_CLAVE_LARGA_Y_UNICA'),
    'role' => 'admin',
]);
```

Permisos:

```bash
sudo chown -R www-data:www-data /var/www/cafeteria/storage /var/www/cafeteria/bootstrap/cache
sudo chmod -R 775 /var/www/cafeteria/storage /var/www/cafeteria/bootstrap/cache
```

## 5. Nginx + HTTPS

Edita `deploy/nginx.conf` y reemplaza los dominios y la versión de PHP-FPM. Luego:

```bash
sudo cp deploy/nginx.conf /etc/nginx/sites-available/cafeteria
sudo ln -s /etc/nginx/sites-available/cafeteria /etc/nginx/sites-enabled/cafeteria
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t

# Primero valida HTTP; Certbot reescribe los bloques TLS si hace falta.
sudo certbot --nginx -d cafeteria.example.com -d ws.cafeteria.example.com
sudo systemctl reload nginx
```

DNS necesario:

| Registro | Tipo | Valor |
|---|---|---|
| `cafeteria.example.com` | A | IP del servidor |
| `ws.cafeteria.example.com` | A | IP del servidor |

Verifica que `https://cafeteria.example.com/up` responda `200 OK`.

## 6. Reverb y colas con systemd

```bash
sudo cp deploy/cafeteria-reverb.service /etc/systemd/system/
sudo cp deploy/cafeteria-queue.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now cafeteria-reverb cafeteria-queue

systemctl status cafeteria-reverb --no-pager
journalctl -u cafeteria-reverb -f
```

El POS debe mostrar **● Tiempo real**. Si muestra **○ Auto-refresco**, revisa:

1. `REVERB_HOST`/`REVERB_PORT`/`REVERB_SCHEME` en `.env`.
2. `sudo systemctl status cafeteria-reverb`.
3. El proxy `wss` de Nginx y el certificado de `ws.tudominio.com`.
4. `php artisan optimize:clear` si cambiaste variables y la configuración está cacheada.

## 7. Scheduler de Laravel

```bash
sudo crontab -u www-data -e
```

```cron
* * * * * cd /var/www/cafeteria && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

## 8. Backups automáticos

> **Nota — desarrollo local (SQLite):** para el entorno de desarrollo existe `php artisan backup:run`,
> que respalda `database.sqlite` (copia en caliente vía `VACUUM INTO`), `storage/app/public` y `.env`
> en `storage/backups/` (rotación a los últimos 7 backups; `BACKUPS_TO_KEEP` en `.env`). Corre
> diariamente a las **03:30** vía el scheduler (`bootstrap/app.php`). El script `deploy/backup.sh`
> documentado aquí es para **producción con MySQL**; no usar uno en lugar del otro.

```bash
sudo mkdir -p /etc/cafeteria /var/backups/cafeteria
sudo install -m 600 deploy/backup.env.example /etc/cafeteria/backup.env
sudo nano /etc/cafeteria/backup.env       # credenciales reales
sudo install -m 755 deploy/backup.sh /usr/local/bin/cafeteria-backup
sudo /usr/local/bin/cafeteria-backup
```

Programar a las 03:00 y revisar el resultado:

```bash
sudo crontab -e
```

```cron
0 3 * * * /usr/local/bin/cafeteria-backup >> /var/log/cafeteria-backup.log 2>&1
```

Copia los archivos generados fuera del servidor (rsync, S3, otro disco). Un backup que solo vive en el mismo servidor no protege ante un fallo de disco.

### Restaurar un backup

```bash
# Base de datos
gunzip -c /var/backups/cafeteria/cafeteria-db-YYYYMMDD-HHMMSS.sql.gz | \
  mysql -h 127.0.0.1 -u cafeteria -p cafeteria

# Imágenes de productos
tar -xzf /var/backups/cafeteria/cafeteria-storage-YYYYMMDD-HHMMSS.tar.gz \
  -C /var/www/cafeteria/storage/app
sudo chown -R www-data:www-data /var/www/cafeteria/storage/app/public
```

Prueba una restauración completa al menos una vez al trimestre.

## 9. Actualizar una instalación existente

Antes de actualizar, espera a que el **CI** del commit esté en verde. Si el workflow falla, no despliegues esa versión.

```bash
cd /var/www/cafeteria
sudo -u www-data php artisan down

git pull --ff-only
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan optimize

sudo systemctl restart cafeteria-reverb cafeteria-queue
sudo -u www-data php artisan up
```

## 10. Checklist de seguridad antes de abrir al público

- [ ] `APP_ENV=production`, `APP_DEBUG=false`.
- [ ] `APP_KEY`, `DB_PASSWORD`, `REVERB_APP_SECRET` únicos y fuera de Git.
- [ ] Usuarios demo (`admin@test.com`, etc.) eliminados o con clave cambiada.
- [ ] HTTPS funcionando y `SESSION_SECURE_COOKIE=true`.
- [ ] Firewall: solo 22 (restringido), 80 y 443 expuestos; MySQL y Reverb escuchan en `127.0.0.1`.
- [ ] Backups diarios + copia externa + restauración probada.
- [ ] `php artisan about` y revisión de logs (`storage/logs/laravel.log`, `journalctl -u cafeteria-reverb`).
- [ ] Monitorización del endpoint `/up`.
- [ ] Impresora de red aislada de Internet; solo accesible desde la LAN del local.

## 11. Verificación posterior al despliegue

1. Login con el administrador real.
2. Crear un producto, una mesa y un pedido de prueba.
3. Ver la comanda en cocina (KDS) y la sincronización en tiempo real.
4. Cobrar, imprimir ticket y comprobar el Reporte Z y el CSV.
5. Registrar una entrada de inventario y revisar la auditoría.
6. Probar la impresora desde **Admin → Impresora**.
7. Descargar/restaurar un backup en un entorno de pruebas.
