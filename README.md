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
   - **Credenciales locales de prueba** (creadas por el seeder):
     - **Email:** `barista@test.com`
     - **Contraseña:** `password`
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

---

## 📂 Estructura de Rutas Principales

| Ruta | Método | Descripción | Acceso |
|------|--------|-------------|--------|
| `/` | GET | Redirige a `/admin` | Público |
| `/login` | GET/POST | Autenticación | Público |
| `/carta` | GET | Carta Digital QR | Público |
| `/admin` | GET | Panel de administración | Auth |
| `/admin/cierre-turno` | GET | Reporte Z (Fase 7) | Auth |
| `/admin/productos` | Resource | CRUD de productos | Auth |
| `/pos` | GET | Punto de Venta | Auth |
| `/kds` | GET | Cocina - Comandas en vivo | Auth |
| `/api/pedidos` | POST | Crear pedido | Auth |
| `/api/kds/pedidos` | GET | Listado paginado para cocina | Auth |
| `/api/kds/pedidos/{id}/estado` | PATCH | Cambiar estado de comanda | Auth |
| `/api/pedidos/{id}/pagar` | POST | Cobrar pedido y liberar mesa | Auth |
| `/api/pedidos/{id}/ticket` | GET | Ticket de cobro / factura | Auth |
| `/api/pedidos/{id}/comanda` | GET | Comanda de cocina para impresión (Fase 9) | Auth |
| `/api/mesas` | GET | Listado de mesas con estado | Auth |
| `/api/productos` | GET | Listado de productos disponibles | Auth |

---

## 🗺️ Estado actual y próximos pasos

Las fases funcionales 1 a 9 están implementadas y el flujo central de pedidos/cobros cuenta ahora con controles de integridad y pruebas. La suite actual pasa con **9 pruebas y 36 aserciones**. El proyecto sigue en desarrollo: esto no equivale a una auditoría de seguridad ni a una certificación para producción.

Orden sugerido para continuar:

### Fase 10: Exportación del Reporte Z a CSV
- Permitir descargar el desglose de ventas en formato CSV para importar en contabilidad externa.

### Fase 11: Roles de Usuario y Permisos
- Diferenciar roles (Administrador, Barista, Cocina) con permisos granulares sobre cada módulo.

### Fase 12: Notificaciones en Tiempo Real (WebSockets / Pusher)
- Notificar al POS y KDS al instante cuando se crea o actualiza un pedido, sin necesidad de refrescar.

### Fase 13: Impresión física automática (red local)
- Conectar directamente a impresoras térmicas de red (Epson/Xprinter) en lugar de depender de `window.print()` del navegador.

---

## 💡 Notas para Futuros Desarrolladores (IA / Humanos)

- Para correr el proyecto usar `npm run dev --host` y `php artisan serve --host=0.0.0.0 --port=8000`.
- Las migraciones actuales incluyen soporte para imágenes (`imagen`), métodos de pago (`metodo_pago`) y propinas (`propina`).
- La carpeta `storage/app/public` ya ha sido enlazada usando `storage:link`.
- AlpineJS está integrado de manera inline en los archivos Blade (POS y KDS) para reactividad sin compilación compleja.
- El PDF del Reporte Z usa `html2pdf.js` vía CDN, por lo que no requiere `composer require` de librerías PDF adicionales.
- La comanda de cocina (Fase 9) se imprime automáticamente al crear un pedido desde el POS; se abre en una ventana `_blank` con `width=350,height=600` optimizada para térmica de 72mm.
- Todas las rutas operativas (`/pos`, `/kds`, `/admin`, `/admin/cierre-turno`) están protegidas por el middleware `auth`.
- Ejecutar `php artisan test` para correr toda la suite de pruebas; ejecutar `vendor/bin/pint --test` para verificar formato PHP.

---

Última actualización: **16 de octubre de 2026**. Fases funcionales 1–9 implementadas; refuerzo de integridad de pedidos/cobros y pruebas de integración añadidos.