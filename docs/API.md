# Café Rosé — Documentación de la API

Esta aplicación **no expone una API REST pura**: todos los endpoints "API" son rutas web
de Laravel autenticadas por **sesión** (cookie `laravel_session`) tras hacer login en `/login`.
La autorización adicional se aplica con el middleware de rol (`role:`); ver
[Roles y permisos](#roles-y-permisos).

- Base URL local: `http://localhost:8000`
- Todas las respuestas JSON usan `application/json; charset=UTF-8` (o `text/csv` en el CSV de Reporte Z).
- Errores de validación devuelven **422** (Laravel `ValidationException`, `message` + `errors`).
- Acceso denegado por rol devuelve **403**; invitado redirigido a `/login` devuelve **302**.
- Salud del sistema: `GET /up` (sin autenticación).

## Índice

- [Autenticación](#autenticación)
- [Roles y permisos](#roles-y-permisos)
- [Rutas públicas](#rutas-públicas)
- [Panel de administración (role:admin)](#panel-de-administración-roleadmin)
- [Punto de Venta (role:admin,barista)](#punto-de-venta-roleadminbarista)
- [Cocina / KDS (role:admin,cocina)](#cocina--kds-roleadmincocina)
- [Accesibles por todos los roles autenticados](#accesibles-por-todos-los-roles-autenticados)
- [WebSockets (Laravel Reverb)](#websockets-laravel-reverb)
- [Modelos de datos](#modelos-de-datos)

---

## Autenticación

| Método | Ruta | Descripción | Acceso |
|---|---|---|---|
| GET | `/login` | Formulario de inicio de sesión | Público |
| POST | `/login` | Autenticar; redirige al módulo según el rol | Público |
| POST | `/logout` | Cerrar sesión y redirigir a `/login` | Autenticado |

### `POST /login`

**Body (form-data)**

| Campo | Tipo | Reglas |
|---|---|---|
| `email` | string | requerido, email |
| `password` | string | requerido |
| `remember` | bool | opcional |

**Respuesta exitosa**: **302** → `/admin` (admin), `/pos` (barista), `/kds` (cocina).
**Fallo**: **302** de vuelta con error `email` (credenciales no coinciden).
**Rate limiting**: `POST /login` admite **5 intentos por minuto** por IP; al superarlo responde **429**. Los intentos fallidos quedan auditados como `login_fallido` (sin guardar la contraseña).
**Cuenta desactivada**: un usuario con `users.activo = false` no puede iniciar sesión; recibe un mensaje indicando que contacte al administrador.

## Roles y permisos

| Rol | Valor en `users.role` | Destino al login |
|---|---|---|
| Administrador | `admin` | `/admin` |
| Barista | `barista` | `/pos` |
| Cocina | `cocina` | `/kds` |

| Módulo / Recurso | admin | barista | cocina |
|---|:---:|:---:|:---:|
| `/admin/*`, `/admin/cierre-turno*`, productos, usuarios, inventario, auditoría, impresora | ✅ | ❌ | ❌ |
| `/pos`, `/api/pedidos*`, `/api/mesas`, `/api/productos`, ticket | ✅ | ✅ | ❌ |
| `/kds`, `/api/kds/pedidos*` | ✅ | ❌ | ✅ |
| `/api/pedidos/{id}/comanda` | ✅ | ✅ | ✅ |
| `/carta`, `/login` | Público | Público | Público |

Un usuario autenticado sin permiso recibe **403**.

## Rutas públicas

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/` | Redirige al módulo del rol (o `/login` si es invitado) |
| GET | `/carta` | Carta digital pública (mobile-first) |
| GET | `/up` | Health check de Laravel |

## Panel de administración (role:admin)

### `GET /admin` — Dashboard

Vista HTML con resumen: ventas de hoy (**solo pedidos `pagado`**), pedidos del día, mesas ocupadas y total de productos. Incluye generador de QR hacia `/carta`.

### `GET /admin/productos` — CRUD de productos

Resource de Laravel, **excluyendo `show`**: `index`, `create`, `store`, `edit`, `update`, `destroy`.

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/admin/productos` | Tabla de administración |
| GET | `/admin/productos/create` | Formulario de creación |
| POST | `/admin/productos` | Crear producto |
| GET | `/admin/productos/{producto}/edit` | Formulario de edición |
| PUT/PATCH | `/admin/productos/{producto}` | Actualizar producto |
| DELETE | `/admin/productos/{producto}` | Eliminar (ver regla de abajo) |

**Campos del producto**: `nombre` (req), `descripcion`, `precio` (req, numérico),
`categoria` (req: `coffee` \| `pastries` \| `sandwiches` \| `cold_drinks`),
`imagen` (archivo local, se guarda en `storage/app/public/productos`),
`disponible` (bool), `vegetariano` (bool), `destacado` (bool),
`alergenos` (array de strings, p. ej. `["Gluten","Lácteos"]`).

> **Regla anti-pérdida de historial**: un producto con pedidos históricos **no puede eliminarse**
> (delvolvería `ON DELETE CASCADE` sobre `detalle_pedidos` y descuadraría los reportes).
> El sistema lo bloquea con un mensaje; la vía correcta es desactivarlo (`disponible=false`).
> Un producto sin pedidos históricos sí puede eliminarse.

### `GET /admin/cierre-turno` — Reporte Z (HTML)

**Query params** (opcionales en ambos):

| Param | Valores | Default |
|---|---|---|
| `periodo` | `dia` \| `mes` \| `anio` | `dia` |
| `valor` | `YYYY-MM-DD` (día), `YYYY-MM` (mes, se normaliza a día 1), `YYYY` (año) | hoy |

Resumen: Ventas Netas, Propinas, Total a Cuadrar y Pedidos Pagados; desglose por método
(efectivo / tarjeta / transferencia) y detalle de pedidos pagados. Exportación a PDF vía html2pdf.js (cliente).

### `GET /admin/cierre-turno/csv` — Reporte Z (CSV)

Mismos filtros (`periodo`, `valor`). Descarga directa (`Content-Disposition: attachment`),
archivo `reporte-z-{periodo}-{YYYYMMDD}.csv` con:

- **BOM UTF-8** y separador `;`, decimales con `,` y miles con `.` (formato español, listo para Excel).
- Secciones: cabecera, resumen, desglose por método, detalle con fecha/hora/mesa/barista.
- **Solo pedidos pagados** (igual que el Reporte Z).

### `GET /admin/usuarios` — Gestión de usuarios (Fase 17)

CRUD de cuentas para el administrador, con protección de seguridad:

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/admin/usuarios` | Lista de usuarios (rol, estado, última gestión) |
| GET | `/admin/usuarios/create` | Formulario de creación |
| POST | `/admin/usuarios` | Crear cuenta (nombre, email, rol, contraseña ≥ 8) |
| GET | `/admin/usuarios/{usuario}/edit` | Formulario de edición (datos + reset de contraseña + activar/desactivar) |
| PUT/PATCH | `/admin/usuarios/{usuario}` | Actualizar nombre/email/rol |
| POST | `/admin/usuarios/{usuario}/reset-password` | Restablecer contraseña sin ver la anterior |
| POST | `/admin/usuarios/{usuario}/toggle` | Activar / desactivar la cuenta |

**Reglas de seguridad**

- Un usuario **desactivado** no puede iniciar sesión.
- Nadie puede **desactivarse a sí mismo** ni **cambiar su propio rol**.
- El **último administrador activo** no puede ser degradado ni desactivado.
- Las contraseñas **nunca** se registran en auditoría ni se muestran en pantalla.
- Crear, editar, restablecer, activar y desactivar quedan registrados en `auditorias` (módulo `usuarios`).

## Punto de Venta (role:admin,barista)

### `GET /pos` — Interfaz POS

### `GET /api/mesas` — Mesas con estado

Lista todas las mesas ordenadas por `numero`. Cada mesa incluye relación `pedidos`
(activos: `pendiente|preparando|listo|entregado`, el más reciente primero, con `garzon`).
Si la mesa está ocupada y tiene pedidos activos, añade: `pedido_id`, `total`, `barista`, `tiempo_ocupada`.

### `GET /api/productos` — Productos disponibles

Todos los productos con `disponible=true` (incluye `precio`, `alergenos`, etc.).

### `POST /api/pedidos` — Crear pedido

**Body JSON**

| Campo | Tipo | Reglas |
|---|---|---|
| `mesa_id` | int | requerido, debe existir en `mesas` |
| `observaciones` | string | opcional |
| `productos` | array | requerido |
| `productos[].producto_id` | int | requerido, existe en `productos` |
| `productos[].cantidad` | int | requerido, min 1 |
| `productos[].notas_especiales` | string | opcional |

**Comportamiento transaccional**

- La mesa se bloquea (`lockForUpdate`) y debe estar **disponible** y **sin pedidos activos** → si no, **422**.
- Los productos se leen **una sola vez** del catálogo (`whereIn` + `lockForUpdate`); el precio
  enviado por el navegador **se ignora**, siempre se usa el precio del catálogo.
- Producto no disponible → **422** producto a producto.
- Crea el pedido (`estado=pendiente`), inserta las líneas en **lote** y marca la mesa `ocupada`.
- Emite `PedidoCreado` (canales `pos` y `cocina`) y **precarga `mesa`** para el broadcast.

**Respuesta** **201**: `{ message, pedido: { id, mesa_id, estado, total, detalles: [...] } }`.

### `POST /api/pedidos/{pedido}/pagar` — Cobrar

**Body JSON**

| Campo | Tipo | Reglas |
|---|---|---|
| `metodo_pago` | string | requerido: `efectivo` \| `tarjeta` \| `transferencia` |
| `propina` | numeric | opcional, min 0 |

- Solo cobra pedidos en estado activo (no `pagado`) → si no, **422**; el cobro es **una sola vez**.
- Marca el pedido como `pagado`, guarda método y propina; libera la mesa si no quedan
  pedidos activos (si no, la mesa sigue `ocupada`).
- Emite `PedidoActualizado` (KDS quita la comanda; POS libera la mesa).

**Respuesta** **200**: `{ message, pedido, impresion }`.

### `POST /api/pedidos/{pedido}/anular` — Anular pedido y devolver stock (Fase 16)

**Body JSON**

| Campo | Tipo | Reglas |
|---|---|---|
| `motivo` | string | requerido, mínimo 5 caracteres, máximo 500 |

**Comportamiento transaccional**

- Solo anula pedidos **activos** (`pendiente`, `preparando`, `listo`, `entregado`); un pedido `pagado` o ya `anulado` devuelve **422**.
- **Repone el stock** de los productos con control de inventario: cada línea genera un movimiento de tipo `devolucion` (vía `InventarioService::registrarDevolucion`) dentro de la misma transacción, con `stock_anterior`/`stock_nuevo` registrados.
- Marca el pedido como `anulado` (nuevo estado terminal).
- **Libera la mesa solo si no quedan otros pedidos activos** en ella.
- Emite `pedido.actualizado` (el KDS quita la comanda y el POS libera la mesa) y registra **auditoría** (`anular`, módulo `pedidos`) con el motivo.

**Respuesta** **200**: `{ message, pedido_id, estado: "anulado", mesa_estado }`.

### `GET /api/pedidos/{pedido}/ticket` — Ticket de cobro

Vista HTML optimizada para impresión térmica (`window.print()`), con desglose
por línea, subtotal, propina y total.

## Cocina / KDS (role:admin,cocina)

### `GET /kds` — Interfaz KDS

### `GET /api/kds/pedidos` — Listado paginado (JSON)

**Query params**

| Param | Default | Notas |
|---|---|---|
| `estado` | (activos) | `pendiente` \| `preparando` \| `listo` \| `entregado`; sin valor → solo activos |
| `page` | 1 | página |
| `per_page` | 20 | tamaño de página |

**Respuesta**: estructura de paginación de Laravel `{ data: [...], current_page, per_page, total, ... }`.
Cada pedido incluye `mesa` y `detalles.producto` (payload mínimo).

### `PATCH /api/kds/pedidos/{pedido}/estado` — Cambiar estado

**Body JSON**: `{ "estado": "pendiente|preparando|listo|entregado" }`

- Solo pedidos **activos**; uno `pagado` devuelve **422**.
- **No acepta** `pagado` como entrada (el cobro solo ocurre vía `/pagar`).
- Emite `PedidoActualizado`.

## Accesibles por todos los roles autenticados

### `GET /api/pedidos/{pedido}/comanda` — Comanda de cocina

Vista HTML optimizada para impresora térmica de **72 mm** (`window.print()`), con cabecera,
datos del pedido, ítems con notas y estado. Todos los roles pueden reimprimir.

## WebSockets (Laravel Reverb)

- Broadcaster: **Reverb** (ver `REVERB_*` en `.env`); `ShouldBroadcastNow` → **no requiere worker de colas**.
- Autorización de canales privados: `routes/channels.php` (rol aplicado, mismo esquema que `role:`).
- `POST /broadcasting/auth` — endpoint de auth de Echo/Pusher.

| Canal | Roles | Evento | Contenido |
|---|---|---|---|
| `pos` | admin, barista | `pedido.creado` / `pedido.actualizado` | id, mesa, estado, total |
| `cocina` | admin, cocina | `pedido.creado` / `pedido.actualizado` | id, mesa, estado, total |

Los clientes (`layouts/realtime.blade.php`) usan **laravel-echo + pusher-js** por CDN.
Si el WebSocket no está disponible, ambas vistas hacen **auto-refresco cada 12 s** (solo con la pestaña visible).

## Modelos de datos

| Tabla | Columnas clave |
|---|---|
| `users` | id, name, email unique, password (hash), role (`admin`\|`barista`\|`cocina`), activo (bool, default `true`) |
| `mesas` | id, numero unique, capacidad, estado (`disponible`\|`ocupada`\|`reservada`) |
| `productos` | id, nombre, precio decimal(10,2), categoria, disponible, vegetariano, destacado, imagen, alergenos JSON, controlar_stock, stock, stock_minimo |
| `pedidos` | id, mesa_id FK, user_id FK→users nullOnDelete, estado enum (6: `pendiente`,`preparando`,`listo`,`entregado`,`pagado`,`anulado`), total decimal(10,2), observaciones text, metodo_pago, propina decimal(10,2) |
| `detalle_pedidos` | id, pedido_id FK→pedidos (cascade), producto_id FK→productos (cascade), cantidad int, precio_unitario decimal(10,2), notas_especiales text |
| `inventario_movimientos` | id, producto_id FK→productos (restrictOnDelete), user_id FK nullable, pedido_id FK nullable, tipo (`entrada`\|`ajuste`\|`venta`\|`devolucion`), cantidad, stock_anterior, stock_nuevo, motivo |
| `auditorias` | id, user_id FK nullable, accion, modulo, descripcion, modelo_type/modelo_id (polimórfico), cambios JSON (sin contraseñas), ip, user_agent |
| `cache`, `jobs`, `sessions` | infraestructura Laravel (sqlite) |

Índices: `pedidos.estado` y `pedidos.created_at` (añadidos por índice de rendimiento en la misma versión).

> **Formato de dinero**: en vistas y tickets se muestra el valor crudo (CLP/$, punto de miles);
> en el **CSV de Reporte Z** se usa formato español (`.` miles, `,` decimales) por compatibilidad con Excel.

## Notas para integradores

1. **Autenticación por sesión**: tras `POST /login`, las peticiones deben llevar la cookie
   de sesión; para peticiones JSON, seguir usando el mismo dominio (no hay CORS abierto por defecto).
2. **419 (CSRF)**: `POST`/`PATCH`/`DELETE` desde formularios HTML llevan token
   `X-CSRF-TOKEN`/`_token`; las rutas AJAX del POS ya lo envían.
3. **Comportamiento no contractual**: las rutas `/api/*` pueden cambiar sin versionado;
   documentado es para uso interno del equipo.
