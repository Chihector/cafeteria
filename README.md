# Café Rosé - Sistema de Gestión para Cafetería

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

---

## 🧹 Correcciones de calidad (19 oct 2026)

- El dashboard ahora cuenta como **ventas solo pedidos `pagado`**, igual que el Reporte Z (antes sumaba `listo`/`entregado` sin cobrar).
- **No se puede eliminar un producto con pedidos históricos**: se bloquea con un mensaje y se sugiere desactivarlo, evitando que el `ON DELETE CASCADE` borre líneas de pedidos pasados y descuadre reportes.
- Se eliminó la ruta `productos.show` (no existía el método en el controlador), evitando errores 500.
- El seeder pasó a ser **idempotente** (`updateOrCreate`/`firstOrCreate`), por lo que puede ejecutarse varias veces sin duplicar datos ni fallar por emails repetidos.
- **KDS:** se corrigió el JavaScript del tablero (faltaban comas entre métodos/getters, lo que impedía que Alpine inicializara la pantalla) y el nombre de las listas (`pedidosPendiente` → `pedidosPendientes`, `pedidosListo` → `pedidosListos`), que hacía que las columnas de **Pendientes** y **Listos** siempre aparecieran vacías.

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
| `/pos` | GET | Punto de Venta | Admin / Barista |
| `/kds` | GET | Cocina - Comandas en vivo | Admin / Cocina |
| `/api/pedidos` | POST | Crear pedido | Admin / Barista |
| `/api/kds/pedidos` | GET | Listado paginado para cocina | Admin / Cocina |
| `/api/kds/pedidos/{id}/estado` | PATCH | Cambiar estado de comanda | Admin / Cocina |
| `/api/pedidos/{id}/pagar` | POST | Cobrar pedido y liberar mesa | Admin / Barista |
| `/api/pedidos/{id}/ticket` | GET | Ticket de cobro / factura | Admin / Barista |
| `/api/pedidos/{id}/comanda` | GET | Comanda de cocina para impresión (Fase 9) | Todos los roles |
| `/api/mesas` | GET | Listado de mesas con estado | Admin / Barista |
| `/api/productos` | GET | Listado de productos disponibles | Admin / Barista |
| `/broadcasting/auth` | POST | Autorización de canales privados (Fase 12) | Según canal |

---

## 🗺️ Estado actual y próximos pasos

Las fases funcionales 1 a 12 están implementadas: flujo de pedidos/cobros con controles de integridad, permisos por rol y sincronización en tiempo real entre POS y cocina. La suite actual pasa con **27 pruebas y 157 aserciones** (más una verificación E2E con Reverb real). El proyecto sigue en desarrollo: esto no equivale a una auditoría de seguridad ni a una certificación para producción.

Orden sugerido para continuar:

### Fase 13: Impresión física automática (red local)
- Conectar directamente a impresoras térmicas de red (Epson/Xprinter) en lugar de depender de `window.print()` del navegador.

---

## 💡 Notas para Futuros Desarrolladores (IA / Humanos)

- Para correr el proyecto usar `npm run dev --host`, `php artisan serve --host=0.0.0.0 --port=8000` y, para tiempo real, `php artisan reverb:start --host=0.0.0.0 --port=8080`.
- Las migraciones actuales incluyen soporte para imágenes (`imagen`), métodos de pago (`metodo_pago`), propinas (`propina`) y roles (`role`).
- La carpeta `storage/app/public` ya ha sido enlazada usando `storage:link`.
- AlpineJS está integrado de manera inline en los archivos Blade (POS y KDS) para reactividad sin compilación compleja.
- El PDF del Reporte Z usa `html2pdf.js` vía CDN, por lo que no requiere `composer require` de librerías PDF adicionales.
- El CSV del Reporte Z (Fase 10) usa `fputcsv` nativo de PHP con BOM UTF-8, separador `;` y decimales con coma; pensado para importar directo en Excel/contabilidad.
- La comanda de cocina (Fase 9) se imprime automáticamente al crear un pedido desde el POS; se abre en una ventana `_blank` con `width=350,height=600` optimizada para térmica de 72mm.
- Todas las rutas operativas están protegidas por `auth` y, además, por el middleware de rol (`role:admin`, `role:admin,barista`, `role:admin,cocina`). El rol vive en `users.role` (`admin` | `barista` | `cocina`).
- El alias `role` se registra en `bootstrap/app.php`; el middleware está en `app/Http/Middleware/EnsureUserHasRole.php`.
- Tiempo real (Fase 12): eventos en `app/Events/`, canales en `routes/channels.php` y cliente en `resources/views/layouts/realtime.blade.php`. Se usa `ShouldBroadcastNow`, así que **no hace falta un worker de colas**; sí hace falta Reverb corriendo. `laravel-echo` y `pusher-js` se cargan por CDN.
- Ejecutar `php artisan test` para correr toda la suite de pruebas; ejecutar `vendor/bin/pint --test` para verificar formato PHP.

---

Última actualización: **19 de octubre de 2026**. Fase 12 (tiempo real con Reverb) completada; correcciones del KDS (JS y columnas Pendientes/Listos). Suite: 27 pruebas / 157 aserciones.