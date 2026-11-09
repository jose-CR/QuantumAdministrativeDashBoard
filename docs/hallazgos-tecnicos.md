# Hallazgos técnicos — revisión del panel

Primera revisión: 2026-11-07 (solo lectura). Actualizado: 2026-11-09, con el estado de cada
hallazgo. Todos los de la lista original quedaron cerrados y probados.

| # | Hallazgo | Estado | Cómo quedó |
|---|----------|--------|------------|
| 1 | Los pagos no registran quién los cobró (`RegisterPaymentService` sin `user_id`) | Cerrado | El servicio guarda `user_id` (`auth()->id()` por defecto, con parámetro para imports y tests). Los 41 pagos históricos siguen sin usuario: ese dato no se puede inventar. |
| 2 | El sistema de alertas no funciona (`AlertService` importaba `App\Models\Client`, usaba `client_id` en vez de `customer_id`, observer vacío, sin scheduler) | Cerrado | `AlertService` usa `Customer`/`customer_id`; `GenerateAlertsCommand` genera, `AlertNotificationService` envía y `InstallmentObserver` completa la alerta al pagar; hay contenedores `worker` y `scheduler`. Verificado: 115 alertas generadas y enviadas, sin duplicados en la segunda corrida. |
| 3 | `ClientExporter`/`ClientImporter` apuntaban a `App\Models\Client` (inexistente) y manejaban campos que `customers` no tiene (`gender`, `birth_date`, `occupation`, `monthly_income`…) | Cerrado | Eliminados: no los usaba nadie y duplicaban `CustomerExporter`/`CustomerImporter`/`CustomerRules`, que sí están alineados con la tabla real. |
| 4 | `partial`/`late` nunca se escriben, y `CloseCreditService` solo miraba `pending` | Cerrado | `CloseCreditService` usa `Installment::UNPAID_STATUSES`: una cuota atrasada ya no deja cerrar el crédito con deuda. Una cuota con saldo 0 se marca pagada al recalcular (`ApplyPaymentService`), que era el caso que dejaba créditos abiertos. |
| 5 | Sin tests reales (`phpunit.xml` apuntaba a la base real) | Cerrado | Las pruebas corren contra `quantum_test` (ver *Base de pruebas*), con `DatabaseTransactions`. Una prueba falla si la base no termina en `_test`. 66 pruebas entre inventario, créditos, export y panel. |
| 6 | 8 créditos `active` con la última cuota en `pending` y saldo 0: el total daba 0 pero no se cerraban nunca | Cerrado | Migración `2026_11_09_160000_close_paid_credits`: 8 cuotas a `paid` y 8 créditos a `completed` (36 activos → 28, 14 completados → 22). Respaldo previo en `storage/app/backups/creditos-antes-de-cerrar-2026-11-09.sql`. |

## Pendientes que no vienen de esta revisión

- Los pagos anteriores al arreglo siguen con `user_id` en NULL: no hay forma de saber quién los cobró.
- La prueba de descarga de export se salta si la base no tiene exports completados (no crea uno propio).

## Base de pruebas

`phpunit.xml` fija `DB_DATABASE=quantum_test`, así los tests nunca tocan la base de desarrollo. Para
recrearla desde cero:

```bash
docker exec quantum-db psql -U quantum -d postgres -c "create database quantum_test owner quantum"
docker exec -e DB_DATABASE=quantum_test quantum-web php artisan migrate --force
docker exec -e DB_DATABASE=quantum_test quantum-web php artisan db:seed --force
docker exec quantum-web php artisan test
```

`db:seed` corre `ShieldSeeder` (roles y permisos, generado con `shield:seeder` desde los roles reales)
y los seeders de datos de ejemplo. Los tests que necesitan un usuario con rol lo crean solos
(`$this->admin()` en `tests/TestCase.php`), así que la base no depende de usuarios de desarrollo.

## Respaldos

Todos en `storage/app/backups/`:

- `inventario-antes-migracion.sql` — antes de hacer genérico el inventario.
- `descripciones-antes-de-limpiar.sql` — antes de limpiar el HTML de las descripciones.
- `creditos-antes-de-cerrar-2026-11-09.sql` — antes de cerrar los 8 créditos.
