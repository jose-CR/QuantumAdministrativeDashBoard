# Hallazgos técnicos — revisión del panel (2026-11-07)

Revisión de solo lectura del proyecto en `main` (commit 709fbaf). Nada de esto se corrigió todavía;
queda como deuda técnica a atacar cuando se autorice.

## 1. Los pagos no registran quién los hizo

`app/Services/RegisterPaymentService.php` crea el `PaymentHistory` sin `user_id`, aunque la columna
existe, la tabla lo muestra (`PaymentHistoriesTable.php:25`) y el export lo incluye
(`PaymentHistoriesExporter.php:24`).

- Evidencia: `select count(*) filter (where user_id is null), count(*) from payment_histories;` → **41 de 41** en NULL.
- Efecto: la columna "usuario" sale vacía siempre; no hay auditoría de quién cobró.
- Arreglo mínimo: `'user_id' => auth()->id()` en el `create()` del servicio (y `?int $userId = null`
  como parámetro para imports/tests).

## 2. El sistema de alertas no funciona

Tres fallas encadenadas:

1. `app/Services/Alerts/AlertService.php:6` importa `App\Models\Client`, que **no existe** (el modelo es `App\Models\Customer`). Rompe `create()` y `createUpcoming()` en cuanto se llaman.
2. `createUpcoming()` usa `$installment->credit->client_id`; en `credits` la columna se llama `customer_id`, así que el `client_id` de la alerta queda NULL.
3. `app/Observers/InstallmentObserver.php` está **vacío** (pero registrado en `AppServiceProvider`), y en el contenedor no corre cron ni `schedule:work`, aunque `routes/console.php` agenda `alerts:check` cada minuto.

- Evidencia: `alerts` = **0 filas** con **112 cuotas vencidas** por **$28.524,83**.
- Arreglo: usar `Customer`, `customer_id`, llenar el observer o mover la generación a un comando, y
  correr el scheduler (contenedor con `schedule:work` o cron del host).

## 3. `ClientExporter` y `ClientImporter` apuntan a una clase inexistente

`app/Filament/Exports/Credits/ClientExporter.php:13` declara `$model = Client::class` con
`use App\Models\Client;` (inexistente). El import hace lo mismo. El export de clientes revienta al usarse.

## 4. Los estados `partial` y `late` nunca se escriben

`ApplyPaymentService` deja la cuota en `pending` cuando el pago es parcial ("la cuota conserva su
estado"), y nada marca `late`: el atraso se calcula por fecha con `scopeOverdue`.

- Consecuencia latente: `CloseCreditService` solo verifica `status = 'pending'`, así que una cuota
  `late` con saldo podría dejar cerrar el crédito con deuda. Hoy no ocurre (todas las vencidas siguen
  `pending`), pero el hueco existe.
- Arreglo: marcar `partial` en el pago parcial y usar `Installment::UNPAID_STATUSES` en `CloseCreditService`.

## 5. Sin tests reales

Solo `tests/Unit/ExampleTest.php` y `tests/Feature/ExampleTest.php`. Además `phpunit.xml` apunta a la
base real y no hay `.env.testing`: correr tests con `--env=testing` contra una base `quantum_test`.

## Cambio pendiente de commit

`app/Filament/Admin/Widgets/CashFlowStatsWidget.php` separa "Entradas cobradas" de "Por cobrar".
Es correcto; quedó sin commitear en el árbol de trabajo.
