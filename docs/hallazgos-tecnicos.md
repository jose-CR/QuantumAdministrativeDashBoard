# Hallazgos técnicos — revisión del panel

Primera revisión: 2026-11-07 (solo lectura). Actualizado: 2026-11-09, con el estado de cada
hallazgo. Todos los de la lista original quedaron cerrados y probados.

| # | Hallazgo | Estado | Cómo quedó |
|---|----------|--------|------------|
| 1 | Los pagos no registran quién los cobró (`RegisterPaymentService` sin `user_id`) | Cerrado | El servicio guarda `user_id` (`auth()->id()` por defecto, con parámetro para imports y tests). Los 41 pagos históricos siguen sin usuario: ese dato no se puede inventar. |
| 2 | El sistema de alertas no funciona (`AlertService` importaba `App\Models\Client`, usaba `client_id` en vez de `customer_id`, observer vacío, sin scheduler) | Cerrado | `AlertService` usa `Customer`/`customer_id`; `GenerateAlertsCommand` genera, `AlertNotificationService` envía y `InstallmentObserver` completa la alerta al pagar; hay contenedores `worker` y `scheduler`. Verificado: 115 alertas generadas y enviadas, sin duplicados en la segunda corrida. |
| 3 | `ClientExporter`/`ClientImporter` apuntaban a `App\Models\Client` (inexistente) y manejaban campos que `customers` no tiene (`gender`, `birth_date`, `occupation`, `monthly_income`…) | Cerrado | Eliminados: no los usaba nadie y duplicaban `CustomerExporter`/`CustomerImporter`/`CustomerRules`, que sí están alineados con la tabla real. |
| 4 | `partial`/`late` nunca se escriben, y `CloseCreditService` solo miraba `pending` | Cerrado | `CloseCreditService` usa `Installment::UNPAID_STATUSES`: una cuota atrasada ya no deja cerrar el crédito con deuda. Una cuota con saldo 0 se marca pagada al recalcular (`ApplyPaymentService`), que era el caso que dejaba créditos abiertos. |
| 5 | Sin tests reales (`phpunit.xml` apunta a la base real y no hay `.env.testing`) | Parcial | Hay pruebas de inventario, créditos, export y panel (58 en total). Sigue **pendiente** separar la base de pruebas: hoy los tests corren en transacción contra la base de desarrollo. |
| 6 | 8 créditos `active` con la última cuota en `pending` y saldo 0: el total daba 0 pero no se cerraban nunca | Cerrado | Migración `2026_11_09_160000_close_paid_credits`: 8 cuotas a `paid` y 8 créditos a `completed` (36 activos → 28, 14 completados → 22). Respaldo previo en `storage/app/backups/creditos-antes-de-cerrar-2026-11-09.sql`. |

## Pendientes que no vienen de esta revisión

- Un artículo por cantidad (`tracking = none`) todavía no se puede meter en un crédito: `CreditItem`
  solo apunta a `ArticleUnit` o `Transportation`.
- Separar la base de pruebas (punto 5).
- Los pagos anteriores al arreglo siguen con `user_id` en NULL: no hay forma de saber quién los cobró.

## Respaldos

Todos en `storage/app/backups/`:

- `inventario-antes-migracion.sql` — antes de hacer genérico el inventario.
- `descripciones-antes-de-limpiar.sql` — antes de limpiar el HTML de las descripciones.
- `creditos-antes-de-cerrar-2026-11-09.sql` — antes de cerrar los 8 créditos.
