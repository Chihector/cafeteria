# Café Rosé - Sistema de Gestión para Cafetería

[![CI](https://github.com/chicohector953/cafeteria/actions/workflows/ci.yml/badge.svg)](https://github.com/chicohector953/cafeteria/actions/workflows/ci.yml)

Este proyecto es un sistema web para la gestión de una cafetería, construido con **Laravel 12**, **Alpine.js** y **Tailwind CSS**.

Incluye carta digital, punto de venta, pantalla de cocina/barra y panel de administración. Las operaciones se guardan en la base de datos; el KDS consulta pedidos mediante su API paginada. La interfaz usa un diseño mobile-first en tonos rosados.

## ⚙️ Guía de Instalación Rápida (Para continuar el proyecto)

Sigue estos pasos para levantar el proyecto localmente y poder retomar el desarrollo:

1. **Instalar dependencias de PHP y Node:**
   ```bash
   composer install
   npm install
   ```

2. **Configurar el entorno:**
   - Copia el archivo `.env.example` y renómbralo a `.env` (si aún no existe).
   - Genera la key de Laravel:
     ```bash
     php artisan key:generate
     ```

3. **Base de datos (SQLite por defecto):**
   - Asegúrate de que existe el archivo `database/database.sqlite` (puedes crearlo vacío).
   - Ejecuta las migraciones y seeders para tener datos de prueba actualizados para cafetería (productos como lattes y croissants, mesas y usuario administrador):
     ```bash
     php artisan migrate:fresh --seed
     ```
   - **Credenciales locales de prueba** (creadas por el seeder, una por rol):
     - **Administrador:** `admin@test.com` / `password`
     - **Barista:** `barista@test.com` / `password`
     - **Cocina:** `cocina@test.com` / `password`
   - Estas credenciales son solo para desarrollo. Cámbialas y configura el entorno antes de cualquier despliegue.

4. **Levantar servidores:**
   Debes ejecutar estos dos comandos en terminales separadas:
   ```bash
   npm run dev --host
   ```
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```
   > **Nota:** El uso del flag `--host` permite que puedas acceder a la aplicación desde tu celular ingresando la IP local de tu computador (ej. `http://192.168.x.x:8000`). Esto es crucial para probar las interfaces Mobile-First.

5. **(Opcional) Impresora térmica de red:**
   - Si tienes una Epson/Xprinter en la misma red, agrega al `.env`:
     ```env
     PRINTER_ENABLED=true
     PRINTER_HOST=192.168.1.50
     PRINTER_PORT=9100
     PRINTER_WIDTH=42
     ```
   - Reinicia `php artisan config:clear` y prueba desde **Admin → Impresora**.
   - Si está desactivada o falla, el POS sigue usando la impresión por navegador.

> **Producción:** sigue la guía completa en [`deploy/README.md`](deploy/README.md) (MySQL, Nginx, HTTPS, Reverb con systemd, colas y backups).

---

## 🚀 Avances Actuales (Fases Completadas)

Hasta ahora hemos completado satisfactoriamente la reconversión a formato **Cafetería** con las siguientes áreas funcionales:

### 1. Panel de Administración y Reportes (Admin Dashboard)
- **URL:** `/admin` *(Protegida por Login)*
- **Estado:** ✅ Completado
- **Características:**
  - "Hub" central o Portal de Acceso rápido hacia todas las áreas del sistema.
  - Resumen financiero y operativo en vivo (Ventas de hoy, Pedidos ingresados, Mesas ocupadas, Productos).
  - Integración nativa con generador de Código QR para apuntar a la Carta Digital.

### 2. Carta Digital QR (Mobile-First)
- **URL:** `/carta` *(Pública)*
- **Estado:** ✅ Completado
- **Características:**
  - Interfaz pública optimizada exclusivamente para teléfonos móviles (Mobile-First).
  - Estética femenina, llamativa y premium con paleta de colores rosados vibrantes y pasteles.
  - Listado de productos por categoría (Cafés, Pastelería, Sándwiches, Bebidas Frías) con insignias especiales y visualización de alérgenos.
  - Accesible para cualquier cliente escaneando el QR que se genera en el Admin.

### 3. Punto de Venta (POS) y KDS de Cocina
- **POS URL:** `/pos` *(Protegida por Login)*
- **KDS URL:** `/kds` *(Protegida por Login)*
- **Estado:** ✅ Completado
- **Características:**
  - **POS (Baristas):** Interfaz para tomar pedidos.
  - **Visualizador de Mesas (Grid):** El selector de mesas ahora es una cuadrícula interactiva. Las mesas disponibles se ven en blanco, mientras que las ocupadas se iluminan de rosa indicando el nombre del barista a cargo y el tiempo que lleva ocupada la mesa.
  - **Experiencia Mobile (POS):** El carrito es un panel deslizable (bottom-sheet) en teléfonos para maximizar el espacio de la cuadrícula de productos.
  - **Buscador Rápido (Live Search):** Buscador rápido con AlpineJS para filtrar productos en tiempo real por nombre o descripción.
  - **KDS (Cocina/Barra):** Interfaz para visualización de comandas en vivo. Dividida en tres columnas (Nuevos, En Preparación, Listos) con temporizadores en tiempo real.
  - **Scroll infinito / paginación:** Cada columna del KDS carga más pedidos al llegar al final para no desbordar la interfaz con muchos pedidos simultáneos.

### 4. Seguridad y Autenticación
- **URL:** `/login`
- **Estado:** ✅ Completado
- **Características:**
  - Interfaz de login rediseñada con la nueva paleta de colores de "Café Rosé".
  - Las rutas operativas (`/pos`, `/kds`, `/admin`) requieren autenticación.
  - Credenciales por defecto: `barista@test.com` / `password`.

### 5. Gestión de Menú (CRUD de Productos)
- **URL:** `/admin/productos` *(Protegida por Login)*
- **Estado:** ✅ Completado
- **Características:**
  - Tabla de administración para gestionar el menú completo.
  - Creación y edición de productos con carga de fotografías locales (almacenadas en `storage/app/public/productos`).
  - *Switches* estilo iOS para marcar disponibilidad de stock (activa/desactiva el producto en POS y Carta), opción vegetariana y destaque.
  - Selección de múltiples alérgenos.

### 6. Cierre de Cajas y Facturación
- **Estado:** ✅ Completado
- **Características:**
  - El POS detecta mesas "Ocupadas" y, si el carrito está vacío, muestra el botón de "Cobrar Mesa".
  - Modal dinámico de cobro para seleccionar método de pago (Efectivo, Tarjeta, Transferencia).
  - Cálculo automático de subtotal y propina sugerida (10%) opcional.
  - Generación de un ticket tipo recibo en una ventana emergente (`/api/pedidos/{id}/ticket`), optimizado para impresión térmica (`window.print()`).
  - Al completarse el pago, la mesa se libera y cambia a verde de forma automática.

### 7. Cierre de Turno (Reporte Z) + Exportación a PDF
- **URL:** `/admin/cierre-turno` *(Protegida por Login)*
- **Estado:** ✅ Completado
- **Características:**
  - Filtro escalable por **Día, Mes o Año** (`?periodo=dia|mes|anio&valor=YYYY-MM-DD`).
  - Tarjetas de resumen: Ventas Netas, Propinas, Total a Cuadrar y Pedidos Pagados.
  - Desglose por método de pago (Efectivo / Tarjeta / Transferencia) para cuadrar caja física vs. electrónica.
  - Tabla detalle de pedidos pagados con totales del período.
  - **Exportación a PDF** con un clic usando `html2pdf.js` (por CDN, sin instalar dependencias adicionales).
  - Accesible desde el dashboard `/admin` mediante la tarjeta "Cierre de Turno (Z)".

### 8. Paginación y Mejoras en Cocina (KDS)
- **URL:** `/kds` *(Protegida por Login)*
- **Estado:** ✅ Completado
- **Características:**
  - API `/api/kds/pedidos` soporta paginación (`?estado=`, `?page=`, `?per_page=`).
  - Cada columna del KDS (Pendientes / En Preparación / Listos) carga de forma independiente.
  - **Scroll infinito:** al llegar al final de una columna se cargan más pedidos automáticamente.
  - Botón "Cargar más comandas" como alternativa al scroll.
  - Al cambiar estado de un pedido, se recargan todas las columnas.

### 9. Sistema de Impresión Automática de Comandas
- **Estado:** ✅ Completado
- **Características:**
  - Endpoint `/api/pedidos/{pedido}/comanda` genera una comanda de cocina optimizada para impresora térmica (ancho 72mm).
  - **Impresión automática:** al crear un pedido desde el POS, se abre automáticamente la ventana de impresión de la comanda de cocina (`window.print()`).
  - Vista `resources/views/pos/comanda.blade.php` con cabecera, datos del pedido, listado de ítems con notas especiales, estado "EN PREPARACIÓN" y pie de página.

### 10. Integridad de pedidos y cobros (refuerzo reciente)
- El servidor toma los precios vigentes del catálogo; no confía en el precio enviado por el navegador.
- No permite agregar pedidos a mesas ocupadas ni a mesas que tengan una comanda activa.
- No permite pedir productos no disponibles.
- La creación del pedido y el cobro se procesan en transacciones.
- El cobro solo puede completarse una vez; pagar libera la mesa y el endpoint del KDS no puede marcar un pedido como pagado ni reabrir uno pagado.
- Pruebas de integración en `tests/Feature/PedidoFlowTest.php` cubren estos casos.

### 11. Exportación del Reporte Z a CSV (Fase 10)
- **URL:** `/admin/cierre-turno/csv` *(Protegida por Login)*
- **Estado:** ✅ Completado
- **Características:**
  - Descarga el Reporte Z completo en CSV con los mismos filtros de período que la vista (`?periodo=dia|mes|anio&valor=YYYY-MM-DD`).
  - Archivo con **BOM UTF-8** (tildes y "ñ" correctas en Excel), separador `;`, decimales con coma y miles con punto (formato español listo para contabilidad).
  - Secciones: cabecera del reporte, **resumen** (ventas netas, propinas, total a cuadrar, pedidos pagados), **desglose por método de pago** y **detalle de pedidos pagados** con fecha, hora, mesa, barista y totales.
  - Botón "⬇️ Descargar CSV" junto al de PDF en `/admin/cierre-turno`; el enlace se sincroniza automáticamente con el período y valor seleccionados en el filtro.
  - Pruebas de integración en `tests/Feature/ReporteZCsvTest.php` cubren autenticación, contenido del archivo y exclusión de pedidos no pagados o de otros días.

### 12. Roles de Usuario y Permisos (Fase 11)
- **Estado:** ✅ Completado
- **Roles:** `admin` (Administrador), `barista` (Barista) y `cocina` (Cocina), almacenados en `users.role`.
- **Middleware:** `role:admin`, `role:admin,barista`, `role:admin,cocina` (alias registrado en `bootstrap/app.php`). Un usuario autenticado sin permiso recibe **403**.
- **Redirección por rol al iniciar sesión:** Administrador → `/admin`, Barista → `/pos`, Cocina → `/kds` (también aplica en `/`).
- **Matriz de acceso:**
  | Módulo / Recurso | Administrador | Barista | Cocina |
  |---|:---:|:---:|:---:|
  | Dashboard, productos, Reporte Z y CSV | ✅ | ❌ | ❌ |
  | POS, mesas, productos, crear pedido, cobrar, ticket | ✅ | ✅ | ❌ |
  | KDS, listado de comandas y cambio de estado | ✅ | ❌ | ✅ |
  | Comanda de cocina (reimpresión) | ✅ | ✅ | ✅ |
- La interfaz muestra el nombre y rol del usuario conectado, con botón de salir en POS y KDS.
- Pruebas de integración en `tests/Feature/RoleAccessTest.php` cubren invitados, redirección por rol y la matriz de permisos.

### 13. Notificaciones en Tiempo Real (Fase 12)
- **Estado:** ✅ Completado
- **Motor:** **Laravel Reverb** (WebSockets, protocolo Pusher) con eventos `ShouldBroadcastNow` (no requiere worker de colas).
- **Eventos:**
  - `PedidoCreado` → la comanda aparece al instante en el KDS y el POS refresca el grid de mesas.
  - `PedidoActualizado` → sincroniza cambios de estado y el cobro/liberación de mesa entre POS y cocina.
- **Canales privados** con autorización por rol (reutiliza la Fase 11):
  - `pos` → Administrador y Barista.
  - `cocina` → Administrador y Cocina.
- **Frontend:** `laravel-echo` + `pusher-js` (CDN) en `resources/views/layouts/realtime.blade.php`. POS y KDS muestran un indicador **● Tiempo real / ● En vivo** y un **○ Auto-refresco** cuando el WebSocket no está disponible.
- **Respaldo automático:** si el WebSocket se cae, ambas vistas refrescan cada **12 s** (solo si la pestaña está visible). Así la operación nunca se queda con datos viejos.
- **Avisos:** sonido corto en cocina al entrar una comanda y en el POS al liberarse una mesa.
- **Pruebas:** `tests/Feature/RealtimeEventsTest.php` (6 tests) verifica emisión de eventos, payloads, canales y autorización. Además se validó **end-to-end** con Reverb real: conexión WebSocket, suscripción a canales privados, creación de pedido y recepción de eventos en POS y cocina.

#### Cómo ejecutar el tiempo real en local
```bash
# Terminal 1: servidor web
php artisan serve --host=0.0.0.0 --port=8000

# Terminal 2: servidor WebSocket
php artisan reverb:start --host=0.0.0.0 --port=8080
```
Variables relevantes en `.env` (ya generadas por `install:broadcasting`):
`BROADCAST_CONNECTION=reverb`, `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME`.
El cliente usa el mismo host desde el que se abrió la página cuando `REVERB_HOST` es `localhost`/`127.0.0.1`, lo que permite probar desde el celular con la IP de la LAN (Reverb debe escuchar en `0.0.0.0`). En producción, define `REVERB_HOST` con el dominio real y usa `REVERB_SCHEME=https`.

### 14. Impresión física automática (Fase 13)
- **Estado:** ✅ Completado
- **Motor:** comandos **ESC/POS** enviados por TCP al puerto **9100** de la impresora (Epson TM, Xprinter XP, etc.). No requiere paquetes adicionales.
- **Configuración:** `config/printing.php` + variables `PRINTER_ENABLED`, `PRINTER_AUTO_PRINT`, `PRINTER_HOST`, `PRINTER_PORT`, `PRINTER_TIMEOUT` y `PRINTER_WIDTH` (42 columnas para 80 mm, 32 para 58 mm).
- **Comanda:** al crear un pedido, el servidor imprime directo si la impresora está activa. La respuesta incluye `impresion.ok`; si falla, el POS abre la comanda en el navegador como respaldo.
- **Ticket:** al cobrar se imprime automáticamente el ticket con subtotal, propina, total y método de pago.
- **Panel Admin → Impresora:** estado de configuración, comprobación de conexión y prueba de impresión.
- **Pruebas:** `tests/Feature/ImpresoraTest.php` (7 tests) cubre formato ESC/POS, impresión automática, fallo sin bloquear el pedido, respaldo por navegador y permisos.

### 15. Inventario con stock y movimientos (Fase 14)
- **URL:** `/admin/inventario` *(Admin)*
- **Estado:** ✅ Completado
- **Campos por producto:** `controlar_stock` (optativo), `stock` y `stock_minimo`.
- **Ventas:** si el producto controla stock, cada pedido descuenta unidades dentro de la misma transacción. Si no hay unidades suficientes, el pedido se rechaza con un error claro y la mesa no se ocupa.
- **Movimientos:** tabla `inventario_movimientos` con entradas, ajustes y ventas (stock anterior/nuevo, usuario, pedido y motivo).
- **Alertas:** el dashboard avisa cuando hay productos en o bajo el mínimo; el POS y la carta ocultan los agotados.
- **Edición:** el stock se puede fijar desde la ficha del producto o desde el módulo de inventario.
- **Protección:** un producto con movimientos de inventario no se puede eliminar (se conserva el historial).
- **Pruebas:** `tests/Feature/InventarioTest.php` (7 tests).

### 16. Auditoría de acciones sensibles (Fase 15)
- **URL:** `/admin/auditoria` *(Admin)*
- **Estado:** ✅ Completado
- **Registra:** login/logout, creación/edición/eliminación de productos, creación de pedidos, cambios de estado, cobros, entradas/ajustes de inventario y pruebas de impresora.
- **Datos:** usuario, acción, módulo, descripción, modelo afectado, cambios en JSON, IP y user-agent. Los campos sensibles (contraseñas/tokens) se excluyen.
- **Filtros:** módulo, acción, rango de fechas y búsqueda por descripción/IP.
- **Pruebas:** `tests/Feature/AuditoriaTest.php` (4 tests).

### 17. CI y despliegue a producción
- **CI (integración continua):** es una verificación automática que corre en GitHub, no un servidor ni un despliegue. En cada push o pull request, `.github/workflows/ci.yml` ejecuta:
  - `php artisan test` con PHP 8.2, 8.3 y 8.4.
  - `vendor/bin/pint --test` (formato Laravel).
  - `npm ci && npm run build` (compilación de assets).
- **Producción:** [`deploy/README.md`](deploy/README.md) documenta MySQL, Nginx + HTTPS, Reverb supervisado con systemd, worker de colas, scheduler, backups y checklist de seguridad.
- **Plantillas:** `.env.production.example`, `deploy/nginx.conf`, `deploy/cafeteria-reverb.service`, `deploy/cafeteria-queue.service`, `deploy/backup.sh` y `deploy/backup.env.example`.

---

## 🚀 Mejoras de rendimiento y operaciones (2 nov 2026)

### 18. Optimización de consultas y rendimiento
- **N+1 eliminado en la creación de pedidos**: el catálogo se lee una sola vez con `whereIn(...)->lockForUpdate()->get()->keyBy('id')` en lugar de una consulta por línea de carrito.
- **Inserción en lote de líneas**: las `detalles` del pedido se insertan con un único `INSERT` en vez de un `INSERT` por producto.
- **Broadcast sin carga diferida**: `mesa` se precarga antes de emitir `PedidoCreado` (evita una consulta extra en el canal de tiempo real).
- **Índices nuevos en `pedidos`**: `pedidos.estado` y `pedidos.created_at` (migración `2026_11_02_000000_add_indexes_to_pedidos_table.php`), usados por el KDS, el dashboard y el Reporte Z.
- **Estilo de código**: correcciones aplicadas con `vendor/bin/pint`:
  - `blank_line_after_opening_tag` en modelos (Pedido, Mesa, DetallePedido)
  - `method_chaining_indentation`, `no_unused_imports`, `no_whitespace_in_blank_line`, `blank_line_before_statement` en controladores (CartaController, PosController)
  - `class_definition`, `braces_position`, `blank_line_after_opening_tag` en migraciones

### 19. Backup automático de base de datos y archivos
- **Comando:** `php artisan backup:run`
- **Qué respalda:**
  - La base de datos SQLite usando **`VACUUM INTO`** (copia en caliente, transaccionalmente consistente, segura aunque el servidor esté activo).
  - La carpeta `storage/app/public` (imágenes de productos).
  - Una copia del `.env` (`env.backup`) para poder restaurar la configuración junto con los datos.
- **Rotación automática:** conserva los últimos **7 backups** (configurable con `BACKUPS_TO_KEEP=` en `.env`).
- **Programación:** el backup corre **diariamente a las 03:30** vía el scheduler (`withSchedule` en `bootstrap/app.php`, `withoutOverlapping()`), con `php artisan schedule:run` en un cron o `Schedule::command('backup:run')`.
- **Ubicación:** `storage/backups/YYYY-MM-DD_HHmm/` (ignorada por git; solo se versiona `storage/backups/.gitignore`).
- **Prueba manual:** ejecutado y verificado; el SQLite generado es válido (4 pedidos en el último respaldo de prueba).

### 20. Monitoreo y rotación de logs
- **Logs diarios:** `LOG_CHANNEL=daily` (rotación con 14 días de retención, `LOG_DAILY_DAYS=14`).
- **Contexto de errores enriquecido:** `bootstrap/app.php` añade `user_id`, `role`, `method`, `path` e `ip` al contexto de cualquier excepción reportada (sin reemplazar el logging por defecto de Laravel).
- **Eventos críticos registrados** (`storage/logs/laravel-YYYY-MM-DD.log`):
  - Pedido creado (`Log::info`), pedido cobrado (`Log::info`), descarga del Reporte Z CSV.
- **`.env.example` actualizado** con `LOG_CHANNEL=daily` y `BACKUPS_TO_KEEP=7`.

### 21. Documentación de la API
- **`docs/API.md`**: documentación humana completa (autenticación por sesión, roles, todos los endpoints con ejemplos de request/response).
- **`docs/openapi.yaml`**: especificación OpenAPI 3.0 válida (verificada con linter YAML) para importar en Swagger UI / Postman / Insomnia.

---

### 22. Anulación y devolución de pedidos (Fase 16, 3 nov 2026)
- **Endpoint:** `POST /api/pedidos/{pedido}/anular` *(Admin / Barista)*
- **Estado:** ✅ Completado
- **Motivo obligatorio** (mínimo 5 caracteres), registrado en `auditorias` (acción `anular`) con usuario, IP y cambios.
- **Solo pedidos activos** (`pendiente`, `preparando`, `listo`, `entregado`); un pedido pagado o ya anulado devuelve **422**.
- **Reposición de stock**: si el producto controla stock, se devuelve al inventario con un movimiento de tipo `devolucion` (visible en el historial de inventario), vía `InventarioService::registrarDevolucion()` en la misma transacción que la anulación.
- **La mesa se libera solo si no quedan otros pedidos activos** en ella; si queda alguno, sigue ocupada.
- **Nuevo estado terminal `anulado`**: se amplió el `CHECK` de `pedidos.estado` mediante una migración que reconstruye la tabla de forma atómica en SQLite (`PRAGMA foreign_keys=OFF`, copia tabla → drop → rename + recreación de índices); MySQL/PostgreSQL usan `ALTER` nativo.
- **KDS y POS se sincronizan**: se emite `pedido.actualizado`; la comanda anulada desaparece de la cocina automáticamente (el listado solo devuelve estados activos).
- **POS**: botón **Anular Pedido** bajo "Cobrar Mesa", con modal y confirmación; pide el motivo al usuario.
- **Pruebas:** `tests/Feature/AnulacionPedidoTest.php` (9 tests): reposición exacta de stock, liberación condicional de mesa, idempotencia, auditoría, motivo obligatorio, roles y KDS.

### 23. Gestión de usuarios desde el panel y login robusto (Fase 17, 3 nov 2026)
- **URL:** `/admin/usuarios` *(solo Admin)*
- **Estado:** ✅ Completado
- **Ciclo completo**: crear cuenta (nombre, email, rol, contraseña inicial mín. 8 caracteres), editar nombre/email/rol, **restablecer contraseña** sin ver la anterior, **activar/desactivar** sin borrar (se conserva el historial de pedidos y auditoría).
- **Salvaguardas**: nadie puede desactivarse ni cambiar su propio rol; el **último administrador activo** no puede ser degradado ni desactivado.
- **Login blindado** (quick win de la hoja de ruta):
  - `throttle:5,1` en `POST /login` (máx. 5 intentos por minuto por IP, respuesta 429).
  - **Auditoría de intentos fallidos** (`login_fallido`) y de rechazos por cuenta desactivada — sin guardar contraseñas.
  - Un usuario desactivado **no puede iniciar sesión**.
  - Las contraseñas nunca aparecen en auditoría ni en pantalla.
- **Sidebar**: nuevo acceso "Usuarios" en el panel Admin.
- **Pruebas:** `tests/Feature/UserManagementTest.php` (11 tests): creación, login, desactivación/reactivación, protección de auto-edición y último admin, reset con 8+ caracteres, auditoría sin contraseñas, 403 para barista y render de las páginas.

---

## 🧹 Correcciones de calidad (19 oct 2026)

- El dashboard ahora cuenta como **ventas solo pedidos `pagado`**, igual que el Reporte Z (antes sumaba `listo`/`entregado` sin cobrar).
- **No se puede eliminar un producto con pedidos históricos**: se bloquea con un mensaje y se sugiere desactivarlo, evitando que el `ON DELETE CASCADE` borre líneas de pedidos pasados y descuadre reportes.
- Se eliminó la ruta `productos.show` (no existía el método en el controlador), evitando errores 500.
- El seeder pasó a ser **idempotente** (`updateOrCreate`/`firstOrCreate`), por lo que puede ejecutarse varias veces sin duplicar datos ni fallar por emails repetidos.
- **KDS:** se corrigió el JavaScript del tablero (faltaban comas entre métodos/getters, lo que impedía que Alpine inicializara la pantalla) y el nombre de las listas (`pedidosPendiente` → `pedidosPendientes`, `pedidosListo` → `pedidosListos`), que hacía que las columnas de **Pendientes** y **Listos** siempre aparecieran vacías.

### Correcciones y mejoras (24 oct 2026)

- **Categorías unificadas:** los formularios de producto usaban `cafes/pasteleria/...` mientras el POS y la carta usan `coffee/pastries/sandwiches/cold_drinks`; los productos nuevos no aparecían. Ya están alineados.
- **Agotados:** el POS, la carta y la validación del servidor ocultan/rechazan productos con stock 0 cuando el control está activo.
- **Errores del POS:** ahora muestran el detalle de validación del servidor (por ejemplo, "Stock insuficiente...") en lugar de un mensaje genérico.
- **Inventario transaccional:** las ventas descuentan stock y registran el movimiento en la misma transacción del pedido.
- **Auditoría:** los cambios de producto y pedido guardan solo los campos modificados, sin datos sensibles.
- **Formato:** se aplicó Pint a todo el proyecto para que la verificación de CI parta en verde.

---

## 📂 Estructura de Rutas Principales

| Ruta | Método | Descripción | Acceso |
|------|--------|-------------|--------|
| `/` | GET | Redirige al módulo del rol (o `/login`) | Público |
| `/login` | GET/POST | Autenticación | Público |
| `/carta` | GET | Carta Digital QR | Público |
| `/admin` | GET | Panel de administración | Admin |
| `/admin/cierre-turno` | GET | Reporte Z (Fase 7) | Admin |
| `/admin/cierre-turno/csv` | GET | Export CSV del Reporte Z (Fase 10) | Admin |
| `/admin/productos` | Resource | CRUD de productos | Admin |
| `/admin/usuarios` | Resource | CRUD de usuarios y activación (Fase 17) | Admin |
| `/admin/inventario` | GET | Stock, alertas y movimientos (Fase 14) | Admin |
| `/admin/inventario/{producto}/entrada` | POST | Registrar entrada de stock | Admin |
| `/admin/inventario/{producto}/ajuste` | POST | Fijar stock exacto | Admin |
| `/admin/auditoria` | GET | Historial de acciones sensibles (Fase 15) | Admin |
| `/admin/impresora` | GET | Estado de la térmica de red (Fase 13) | Admin |
| `/admin/impresora/probar` | POST | Enviar impresión de prueba | Admin |
| `/admin/impresora/comprobar` | POST | Comprobar conexión TCP | Admin |
| `/pos` | GET | Punto de Venta | Admin / Barista |
| `/kds` | GET | Cocina - Comandas en vivo | Admin / Cocina |
| `/api/pedidos` | POST | Crear pedido | Admin / Barista |
| `/api/kds/pedidos` | GET | Listado paginado para cocina | Admin / Cocina |
| `/api/kds/pedidos/{id}/estado` | PATCH | Cambiar estado de comanda | Admin / Cocina |
| `/api/pedidos/{id}/anular` | POST | Anular pedido activo y reponer stock (Fase 16) | Admin / Barista |
| `/api/pedidos/{id}/pagar` | POST | Cobrar pedido y liberar mesa | Admin / Barista |
| `/api/pedidos/{id}/ticket` | GET | Ticket de cobro / factura | Admin / Barista |
| `/api/pedidos/{id}/comanda` | GET | Comanda de cocina para impresión (Fase 9) | Todos los roles |
| `/api/mesas` | GET | Listado de mesas con estado | Admin / Barista |
| `/api/productos` | GET | Productos disponibles (excluye agotados) | Admin / Barista |
| `/broadcasting/auth` | POST | Autorización de canales privados (Fase 12) | Según canal |
| `/up` | GET | Health check para monitorización | Público |

---

## 🗺️ Estado actual y próximos pasos

Las fases funcionales **1 a 17** están implementadas: pedidos/cobros con integridad, roles, tiempo real, impresión térmica de red, inventario con stock, auditoría de acciones, **anulación con devolución de stock** (con el nuevo estado terminal `anulado`, tanto en SQLite como MySQL/PostgreSQL) y **gestión de usuarios desde el panel** con login robusto (rate limiting + auditoría de intentos fallidos). También hay **CI** (pruebas + Pint + build), una guía de despliegue a producción con MySQL, Nginx, HTTPS, Reverb supervisado y backups, y las **mejoras operativas del 2 nov 2026**: optimización de consultas (fix N+1, inserts en lote, índices `pedidos.estado`/`created_at`), **backup automático diario** (03:30, rotación ×7), logs diarios con contexto de error y **documentación completa de la API** (`docs/API.md`, `docs/openapi.yaml`). La suite local pasa con **65 pruebas y 324 aserciones** (más una verificación E2E previa con Reverb real). El proyecto sigue en desarrollo: esto no equivale a una auditoría de seguridad externa ni a una certificación para producción; antes de abrir al público hay que completar el checklist de [`deploy/README.md`](deploy/README.md).

### Próximos pasos sugeridos

La hoja de ruta completa, con alcance, criterios de aceptación, estimaciones y decisiones abiertas, está en **[`ROADMAP.md`](ROADMAP.md)**.

Orden inmediato:

1. ~~**Anulación/devolución de pedidos**~~ ✅ 3 nov 2026 (Fase 16).
2. ~~**Gestión de usuarios desde el panel**~~ ✅ 3 nov 2026 (Fase 17).
3. **Impresión por áreas**: varias impresoras (barra/cocina) y selección de ítems por estación.
4. **Calidad y operación**: impresora real, staging MySQL, restauración de backups, E2E y observabilidad.

---

## 💡 Notas para Futuros Desarrolladores (IA / Humanos)

- Para correr el proyecto usar `npm run dev --host`, `php artisan serve --host=0.0.0.0 --port=8000` y, para tiempo real, `php artisan reverb:start --host=0.0.0.0 --port=8080`.
- Las migraciones actuales incluyen imágenes (`imagen`), métodos de pago (`metodo_pago`), propinas (`propina`), roles (`role`), stock (`controlar_stock`, `stock`, `stock_minimo`), movimientos de inventario (`inventario_movimientos`) y auditoría (`auditorias`).
- La carpeta `storage/app/public` ya ha sido enlazada usando `storage:link`.
- AlpineJS está integrado de manera inline en los archivos Blade (POS y KDS) para reactividad sin compilación compleja.
- El PDF del Reporte Z usa `html2pdf.js` vía CDN, por lo que no requiere `composer require` de librerías PDF adicionales.
- El CSV del Reporte Z (Fase 10) usa `fputcsv` nativo de PHP con BOM UTF-8, separador `;` y decimales con coma; pensado para importar directo en Excel/contabilidad.
- Impresión (Fase 13): `App\Services\Printing\PrinterService` envía ESC/POS por TCP; `EscPosFormatter` arma comanda/ticket y translitera a ASCII/CP850. La config vive en `config/printing.php` y `.env`. Si falla, el POS conserva `window.print()` como respaldo.
- Inventario (Fase 14): `Producto` tiene `controlar_stock`, `stock` y `stock_minimo`; `InventarioService` registra entradas, ajustes y ventas. Los productos existentes parten **sin control de stock** para no romper datos.
- Auditoría (Fase 15): `Auditoria::registrar(...)` se llama explícitamente en los puntos sensibles; no hay middleware genérico que registre todo el tráfico.
- Anulación (Fase 16): nuevo estado terminal `anulado` en `pedidos.estado` (migración `2026_11_03_000001_add_anulado_to_pedidos_estado`, reconstruye la tabla en SQLite de forma atómica). Repone stock con movimientos `devolucion` y libera la mesa solo si no queda otro pedido activo. Solo admin/barista con motivo ≥ 5 caracteres.
- Usuarios (Fase 17): `users.activo` (migración `2026_11_03_000002`); un desactivado no puede iniciar sesión; nadie puede desactivarse ni cambiar su propio rol; el último admin activo está protegido. `POST /login` tiene `throttle:5,1` y los fallos se auditan como `login_fallido` (sin contraseñas).
- Todas las rutas operativas están protegidas por `auth` y, además, por el middleware de rol (`role:admin`, `role:admin,barista`, `role:admin,cocina`). El rol vive en `users.role` (`admin` | `barista` | `cocina`).
- El alias `role` se registra en `bootstrap/app.php`; el middleware está en `app/Http/Middleware/EnsureUserHasRole.php`.
- Tiempo real (Fase 12): eventos en `app/Events/`, canales en `routes/channels.php` y cliente en `resources/views/layouts/realtime.blade.php`. Se usa `ShouldBroadcastNow`, así que **no hace falta un worker de colas**; sí hace falta Reverb corriendo. `laravel-echo` y `pusher-js` se cargan por CDN.
- Despliegue: `deploy/README.md` (MySQL, Nginx, HTTPS, systemd, backups). El health check de Laravel está en `/up`.
- Backup automático: `php artisan backup:run` respalda SQLite (`VACUUM INTO`), `storage/app/public` y `.env` en `storage/backups/`; rota a los últimos 7 backups (`BACKUPS_TO_KEEP`) y corre diariamente a las **03:30** (scheduler en `bootstrap/app.php`).
- Logs de aplicación rotan **diariamente** (`LOG_CHANNEL=daily`, 14 días, `LOG_DAILY_DAYS`). Excepciones incluyen contexto de negocio (`user_id`, `role`, `path`) vía `withExceptions` en `bootstrap/app.php`.
- Documentación de la API: **`docs/API.md`** (guía completa) y **`docs/openapi.yaml`** (OpenAPI 3.0 para Swagger UI/Postman).
- Ejecutar `php artisan test` para toda la suite; `vendor/bin/pint --test` para formato; `npm run build` para assets. El CI hace las tres cosas en `.github/workflows/ci.yml`.

---

Última actualización: **3 de noviembre de 2026**. Fases 16 (anulación con devolución de stock) y 17 (gestión de usuarios desde el panel + login con rate limiting y auditoría de fallos) completadas; ROADMAP actualizado con P1 y P2 resueltas. Suite: **65 pruebas / 324 aserciones**. Próxima prioridad: **impresión por áreas** (`ROADMAP.md`).
