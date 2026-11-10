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
| 7 | Borrar un pago o una cuota desde el panel dejaba el crédito descuadrado: las cuotas quedaban pagadas y el saldo no volvía | Cerrado | `RecalculateCreditService`: los pagos son la fuente de verdad y las cuotas se rehacen a partir de ellos. Se dispara al borrar o cambiar el monto de un pago, y al borrar una cuota. |
| 8 | El estado `late` no lo escribía nadie: una cuota vencida seguía `pending` y el panel la mostraba al día | Cerrado | Comando `credits:mark-late` agendado a las 06:45. Sobre la base de desarrollo marcó 110 cuotas por $28.524,83; el total vencido no cambió porque se calculaba por fecha. |
| 9 | El refinanciamiento no tenía pruebas y descontaba el stock dos veces al replicar los bienes | Cerrado | `RefinancingService` replica los ítems dentro de `CreditItem::withoutEvents`; cubierto con tres pruebas (cierre del viejo, bienes sin mover stock, crédito cerrado rechazado). |
| 10 | Dos filas del mismo artículo en un crédito podían dejar el stock en negativo | Cerrado | El modelo valida al guardar contra el stock real (ya descontado por las otras filas) y devuelve el error a la fila. |
| 11 | Refinanciar no pedía permiso y, sobre un cliente, adivinaba cuál crédito refinar (el último activo) | Cerrado | La acción exige `Create:Credit` y sobre un cliente solo aparece si tiene un único crédito activo con saldo; con varios se refina desde el crédito concreto. |
| 12 | Refinanciar copiaba la prima del crédito viejo y dejaba vivas las alertas de las cuotas que quedaban | Cerrado | El crédito nuevo se abre por el saldo pendiente sin prima, y las alertas pendientes de las cuotas refinanciadas se cancelan. |
| 13 | Borrar un crédito refinanciado devolvía al inventario bienes que ya estaban en el crédito nuevo | Cerrado | El `deleting` lee el estado guardado y, si está refinanciado, borra los ítems sin disparar los eventos de stock. |
| 14 | El modal de refinanciar se confirmaba a ciegas | Cerrado | Vista previa de cuota, total e intereses con el mismo servicio que guarda, recalculada al cambiar cuotas o monto. |

## Pendientes que no vienen de esta revisión

- Los pagos anteriores al arreglo siguen con `user_id` en NULL: no hay forma de saber quién los cobró.
- La prueba de descarga de export se salta si la base no tiene exports completados (no crea uno propio).
- El refinanciamiento no copia los pagos del crédito viejo: se arrastra el saldo, que es lo correcto para un documento nuevo. La cadena queda por `refinanced_from_id`; si se quiere el detalle de pagos en el nuevo, es una decisión aparte.

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

## Clientes (auditoría 2026-11-09)

Cerrado — escrituras:

| # | Hallazgo | Cómo quedó |
|---|----------|------------|
| 1 | `full_name` era UNIQUE: dos personas con el mismo nombre no se podían registrar (500) | Fuera el índice; el formulario y el modelo aceptan nombres repetidos |
| 2 | Borrar un cliente se llevaba créditos, cuotas, pagos y alertas por la cascada de la base | Un cliente con créditos o facturas no se borra; el resumen dice cuántos y cuáles |
| 3 | Los adjuntos (polimórficos, sin FK) quedaban huérfanos al borrar | Se borran sus filas y sus archivos junto con el cliente |
| 4 | `document_number` era único global: el NIT de una empresa no podía coincidir con un pasaporte | La identidad es `(document_type, document_number)` |
| 5 | La importación exigía `id` y resolvía por id (desfasa la secuencia) | Resuelve por tipo + número de documento |
| 6 | `document_type` no se validaba en la importación ni en la base | `Rule::in(Customer::DOCUMENT_TYPES)` + validación en el modelo |
| 7 | Municipio y distrito no se validaban contra el catálogo | El modelo valida la geografía completa antes de guardar |

Pendiente — pantallas (próximo turno): mensaje de importación en inglés; el badge de estado de la ficha
nunca se colorea ni se traduce; la ficha muestra `latestCredit` aunque decide con el crédito activo; el
relation manager de créditos muestra el estado crudo; el export saca códigos en vez de nombres; y las
referencias del cliente no tienen pantalla (la pestaña está comentada) con un import muerto a una clase
que no existe.

## Respaldos

Todos en `storage/app/backups/`:

- `inventario-antes-migracion.sql` — antes de hacer genérico el inventario.
- `descripciones-antes-de-limpiar.sql` — antes de limpiar el HTML de las descripciones.
- `creditos-antes-de-cerrar-2026-11-09.sql` — antes de cerrar los 8 créditos.
