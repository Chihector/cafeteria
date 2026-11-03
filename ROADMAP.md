# Hoja de ruta - Café Rosé

> Última actualización: **3 de noviembre de 2026**.
> Estado de partida: fases 1 a 15 completas + **Prioridad 1 (anulación)** y **Prioridad 2 (gestión de usuarios)** + quick win de login (rate limiting y auditoría de intentos fallidos); suite en **65 pruebas / 324 aserciones**, CI configurado y guía de producción lista.

Esta hoja de ruta prioriza el trabajo de la **próxima sesión** y deja un backlog ordenado para las siguientes. La idea es cerrar primero lo que más impacta en la operación diaria del local, sin abrir frentes nuevos antes de estabilizar lo que ya existe.

---

## 1. Cómo retomar mañana

```bash
git pull
php artisan migrate
php artisan test
vendor/bin/pint --test
npm run build

# Desarrollo local
php artisan serve --host=0.0.0.0 --port=8000
php artisan reverb:start --host=0.0.0.0 --port=8080
```

Antes de escribir código, confirmar:

- [ ] El workflow **CI** está en verde en GitHub (pestaña **Actions**).
- [ ] La app abre en `http://localhost:8000` y el login funciona.
- [ ] El POS muestra **● Tiempo real** (Reverb corriendo).
- [ ] `/admin/inventario`, `/admin/auditoria` y `/admin/impresora` cargan sin errores.
- [ ] Si hay impresora física, la prueba desde **Admin → Impresora** imprime.

---

## 2. Prioridad 1: anulación y devolución de pedidos ✅ COMPLETADA (3 nov 2026)

**Problema:** hoy, si un pedido se carga mal, no hay forma de anularlo. El pedido queda activo, la mesa ocupada y el stock descontado. Es el hueco operativo más importante.

**Alcance propuesto:**

- Endpoint `POST /api/pedidos/{pedido}/anular` para **admin y barista**.
- Motivo obligatorio (mínimo 5 caracteres) y confirmación en el POS.
- Solo se pueden anular pedidos en estado `pendiente`, `preparando`, `listo` o `entregado`. Un pedido `pagado` queda fuera de esta fase (ver decisiones abiertas).
- Reponer stock de los productos con `controlar_stock` mediante `InventarioService`, registrando un movimiento de tipo `devolucion`.
- Liberar la mesa si no quedan otros pedidos activos en ella.
- Registrar la acción en auditoría con motivo, usuario y total.
- Refrescar POS/KDS en tiempo real y quitar el pedido de las listas.
- Botón **Anular** en el detalle/mesa del POS, con modal para el motivo.

**Archivos probablemente afectados:**

- `routes/web.php`
- `app/Http/Controllers/PedidoController.php`
- `app/Services/InventarioService.php`
- `app/Models/InventarioMovimiento.php` (nuevo tipo `devolucion`)
- `resources/views/pos/index.blade.php`
- `tests/Feature/AnulacionPedidoTest.php` (nuevo)

**Criterios de aceptación (todos verificados por `tests/Feature/AnulacionPedidoTest.php`, 9 pruebas):**

- [x] Anular un pedido repone exactamente las cantidades vendidas.
- [x] Se crea un movimiento de inventario por producto con stock anterior/nuevo correcto (`tipo=devolucion`).
- [x] La mesa queda `disponible` solo si no tiene otros pedidos activos.
- [x] No se puede anular dos veces el mismo pedido.
- [x] Un barista no puede anular pedidos sin permiso (la ruta exige rol `admin|barista`).
- [x] Queda registro en `/admin/auditoria` con el motivo.
- [x] Pruebas nuevas en verde (65 pruebas / 324 aserciones) y Pint sin cambios pendientes.

**Implementación:** `POST /api/pedidos/{pedido}/anular` (role:admin,barista), nuevo estado terminal `anulado` a través de migración de reconstrucción de constraint (SQLite/MySQL/PostgreSQL), servicio `InventarioService::registrarDevolucion()`, botón **Anular Pedido** en el POS y auditoría `anular/pedidos`.

**Estimación original:** 2 a 3 horas.

---

## 3. Prioridad 2: gestión de usuarios desde el panel ✅ COMPLETADA (3 nov 2026)

**Problema:** los usuarios se crean por seeder o `tinker`. Cambiar una clave o dar de baja a un barista requiere acceso al servidor.

**Alcance propuesto:**

- CRUD `/admin/usuarios` solo para **admin**.
- Crear usuario con nombre, email, rol y contraseña inicial.
- Editar nombre, email y rol.
- Resetear contraseña sin mostrar la actual.
- Activar/desactivar usuarios (en lugar de borrarlos, para conservar pedidos y auditoría).
- Impedir que el admin se desactive o se cambie el rol a sí mismo.
- Impedir eliminar/desactivar al último administrador activo.
- Registrar creación, edición, reset y baja en auditoría (sin guardar contraseñas).

**Archivos probablemente afectados:**

- `app/Http/Controllers/Admin/UserController.php` (nuevo)
- `routes/web.php`
- `resources/views/admin/usuarios/` (nuevo)
- `app/Models/User.php`
- `database/migrations/…_add_activo_to_users_table.php` (nuevo)
- `tests/Feature/UserManagementTest.php` (nuevo)

**Criterios de aceptación (verificados por `tests/Feature/UserManagementTest.php`, 11 pruebas):**

- [x] Un admin puede crear un barista y ese barista puede iniciar sesión.
- [x] Un usuario desactivado no puede iniciar sesión (y su cuenta no se elimina).
- [x] No se puede dejar el sistema sin ningún admin activo (protección de último admin + auto-edición bloqueada).
- [x] Las contraseñas nunca se guardan en auditoría ni se muestran en pantalla.
- [x] Un barista recibe 403 en todas las rutas de usuarios.

**Implementación:** CRUD `/admin/usuarios` (solo admin), campo `activo` en `users` (migración `2026_11_03_000002`), reset de contraseña sin mostrar la anterior, activar/desactivar sin borrar cuentas, auditoría `crear/editar/resetear_password/activar/desactivar` en módulo `usuarios` sin contraseñas. Plus quick win: **rate limiting** en `POST /login` (5 intentos/minuto, `throttle:5,1`) y **auditoría de intentos fallidos** (`login_fallido`, sin guardar contraseñas).

**Estimación original:** 2 horas.

---

## 4. Prioridad 3: impresión por áreas

**Problema:** una sola impresora recibe toda la comanda. En un local con barra y cocina separadas conviene dividir por estación.

**Alcance propuesto:**

- Configuración de varias impresoras (nombre, host, puerto, ancho y categorías asignadas).
- Enviar a cada impresora solo los ítems de sus categorías.
- Si una estación falla, las demás igual imprimen; el POS recibe el detalle por impresora.
- Panel admin para alta/edición/prueba de cada impresora.
- Mantener la impresora única actual como caso por defecto.

**Decisión técnica pendiente:** guardar la configuración en base de datos (gestionable desde el panel) o en `config/printing.php` (más simple, requiere editar `.env`). Para varias impresoras y cambios sin redeploy, conviene base de datos.

**Estimación:** 4 a 6 horas.

---

## 5. Prioridad 4: calidad y operación

| Tarea | Por qué | Estimación |
|---|---|---|
| Probar la impresora térmica real | Validar ESC/POS, acentos, ancho y corte | 1 h |
| Staging con MySQL 8 | La suite local usa SQLite; producción usará MySQL | 2 a 3 h |
| Restauración de backup probada | Un backup sin restore probado no sirve | 1 h |
| Pruebas E2E de navegador (Playwright) | Cubrir POS, KDS y carta en móvil | 4 a 6 h |
| Observabilidad | Alertas de `/up`, Reverb, backups y disco | 2 a 3 h |
| ~~Rate limiting en login~~ | ✅ 3 nov 2026: `throttle:5,1` en `POST /login` | hecho |
| ~~Auditoría de logins fallidos~~ | ✅ 3 nov 2026: evento `login_fallido` auditado (sin contraseñas) | hecho |

---

## 6. Backlog ordenado

1. ~~**Anulación/devolución de pedidos**~~ ✅ 3 nov 2026 (P1).
2. ~~**Gestión de usuarios y claves**~~ ✅ 3 nov 2026 (P2).
3. **Impresión por áreas** (P3).
4. ~~**Login: rate limiting + auditoría de intentos fallidos**~~ ✅ 3 nov 2026 (quick win).
5. **Staging MySQL + restore de backup** (antes de producción).
6. **E2E Playwright** (POS, KDS, carta móvil).
7. **Observabilidad y alertas**.
8. **Reportes históricos** (ventas por rango, productos más vendidos).
9. **Propinas y arqueo de caja más detallado** (si el local lo necesita).
10. **App/PWA offline para el POS** (evaluar recién después de estabilizar).

---

## 7. Decisiones abiertas y resueltas

- ~~**¿Se puede anular un pedido ya pagado?**~~ ✅ Resuelta (3 nov 2026): **no**; solo pedidos activos (pendiente/preparando/listo/entregado). Una devolución de dinero implica un flujo contable/Reporte Z que merece su propia fase.
- ~~**¿Quién puede anular?**~~ ✅ Resuelta (3 nov 2026): admin y barista, con motivo obligatorio (mín. 5 caracteres).
- **¿Varias impresoras por categoría o por ítem?** Propuesta: por categoría; es más simple y cubre barra/cocina.
- ~~**¿Los usuarios se eliminan o se desactivan?**~~ ✅ Resuelta (3 nov 2026): desactivar, para no romper pedidos históricos ni auditoría.
- **¿Dónde vive la configuración de impresoras?** Propuesta: base de datos si se implementa multi-impresora.

---

## 8. Definición de “terminado” para cada tarea

Una tarea se considera cerrada solo si:

- [ ] Tiene pruebas nuevas o actualizadas.
- [ ] `php artisan test` pasa completo.
- [ ] `vendor/bin/pint --test` pasa.
- [ ] `npm run build` compila.
- [ ] Se actualizó el README o la guía correspondiente.
- [ ] Se registró la acción sensible en auditoría (si aplica).
- [ ] Se respetaron los roles y no se expusieron datos sensibles.
- [ ] El cambio quedó commiteado y el CI en verde.

---

## 9. Métricas de referencia

- Suite actual: **65 pruebas / 324 aserciones** (+9 de anulación, +11 de gestión de usuarios sobre las 45 previas).
- Cobertura funcional: pedidos, cobros, anulaciones, roles, KDS, tiempo real, Reporte Z, inventario, auditoría, impresión y gestión de usuarios.
- Objetivo de estabilización: CI verde en cada push y backups restaurables.
