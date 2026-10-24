# Hoja de ruta - Café Rosé

> Última actualización: **24 de octubre de 2026**.
> Estado de partida: fases 1 a 15 completas, suite en **45 pruebas / 247 aserciones**, CI configurado y guía de producción lista.

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

## 2. Prioridad 1: anulación y devolución de pedidos

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

**Criterios de aceptación:**

- [ ] Anular un pedido repone exactamente las cantidades vendidas.
- [ ] Se crea un movimiento de inventario por producto con stock anterior/nuevo correcto.
- [ ] La mesa queda `disponible` solo si no tiene otros pedidos activos.
- [ ] No se puede anular dos veces el mismo pedido.
- [ ] Un barista no puede anular pedidos de otra mesa sin permiso (la ruta ya exige rol).
- [ ] Queda registro en `/admin/auditoria` con el motivo.
- [ ] Pruebas nuevas en verde y Pint sin cambios pendientes.

**Estimación:** 2 a 3 horas.

---

## 3. Prioridad 2: gestión de usuarios desde el panel

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

**Criterios de aceptación:**

- [ ] Un admin puede crear un barista y ese barista puede iniciar sesión.
- [ ] Un usuario desactivado no puede iniciar sesión.
- [ ] No se puede dejar el sistema sin ningún admin activo.
- [ ] Las contraseñas nunca se guardan en auditoría ni se muestran en pantalla.
- [ ] Un barista recibe 403 en todas las rutas de usuarios.

**Estimación:** 2 horas.

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
| Rate limiting en login | Frenar fuerza bruta | 30 min |
| Auditoría de logins fallidos | Hoy solo se registra el login exitoso | 30 min |

---

## 6. Backlog ordenado

1. **Anulación/devolución de pedidos** (P1, mañana).
2. **Gestión de usuarios y claves** (P2).
3. **Impresión por áreas** (P3).
4. **Login: rate limiting + auditoría de intentos fallidos** (quick win).
5. **Staging MySQL + restore de backup** (antes de producción).
6. **E2E Playwright** (POS, KDS, carta móvil).
7. **Observabilidad y alertas**.
8. **Reportes históricos** (ventas por rango, productos más vendidos).
9. **Propinas y arqueo de caja más detallado** (si el local lo necesita).
10. **App/PWA offline para el POS** (evaluar recién después de estabilizar).

---

## 7. Decisiones abiertas

- **¿Se puede anular un pedido ya pagado?** Propuesta para mañana: **no**; solo pedidos activos. Una devolución de dinero implica un flujo contable/Reporte Z que merece su propia fase.
- **¿Quién puede anular?** Propuesta: admin y barista, con motivo obligatorio. Si el local quiere restringirlo, dejarlo solo en admin.
- **¿Varias impresoras por categoría o por ítem?** Propuesta: por categoría; es más simple y cubre barra/cocina.
- **¿Los usuarios se eliminan o se desactivan?** Propuesta: desactivar, para no romper pedidos históricos ni auditoría.
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

- Suite actual: **45 pruebas / 247 aserciones**.
- Cobertura funcional: pedidos, cobros, roles, KDS, tiempo real, Reporte Z, inventario, auditoría e impresión.
- Objetivo de la próxima sesión: **+8 a +12 pruebas** (anulación y usuarios).
- Objetivo de estabilización: CI verde en cada push y backups restaurables.
