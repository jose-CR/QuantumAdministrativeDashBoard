Panel administrativo interno del taller Quantum (venta, ensamblaje y servicio de vehículos eléctricos y baterías de litio). Este documento tiene dos partes:

- [Parte 1: Para usuarios (no desarrolladores)](#parte-1-para-usuarios-no-desarrolladores)
  - [¿Qué es?](#qué-es)
  - [¿Cómo entro?](#cómo-entro)
  - [¿Qué puedo hacer?](#qué-puedo-hacer)
  - [Exportar e importar](#exportar-e-importar)
  - [Reglas de seguridad](#reglas-de-seguridad)
  - [Problemas frecuentes](#problemas-frecuentes)
- [Parte 2: Para desarrolladores](#parte-2-para-desarrolladores)
  - [Stack](#stack)
  - [Infraestructura](#infraestructura)
  - [Modelos principales](#modelos-principales)
    - [`outflows` (salidas / gastos)](#outflows-salidas--gastos)
    - [`inflows` (entradas / ingresos)](#inflows-entradas--ingresos)
    - [`Article`](#article)
    - [Créditos / préstamos (`LoansForm`)](#créditos--préstamos-loansform)
    - [`Attachment` (polimórfico)](#attachment-polimórfico)
  - [Adjuntos y seguridad](#adjuntos-y-seguridad)
  - [Vista de flujo de caja](#vista-de-flujo-de-caja)
  - [Exports](#exports)
  - [Etiquetas](#etiquetas)
  - [Mantenimiento: error de `id` duplicado](#mantenimiento-error-de-id-duplicado)
  - [Convenciones y notas](#convenciones-y-notas)

---

# Parte 1: Para usuarios (no desarrolladores)

## ¿Qué es?

Una aplicación web que corre en la red local del taller. Sirve para llevar el control administrativo: dinero que entra y sale, clientes, artículos, créditos y archivos adjuntos (como facturas).

## ¿Cómo entro?

1. Estar conectado a la red del taller.
2. Abrir el navegador e ir a `http://quantum.test`.
3. Iniciar sesión con tu usuario.

Si la dirección no abre en tu PC, avisa a quien administra el sistema: probablemente falta configurar el DNS en ese equipo.

## ¿Qué puedo hacer?

| Módulo                                 | Para qué sirve                                                                                          |
| -------------------------------------- | ------------------------------------------------------------------------------------------------------- |
| **Flujo de caja** (Entradas y Salidas) | Registrar ingresos (cobros a clientes) y gastos (facturas de proveedores). Cada vista tiene su pestaña. |
| **Clientes**                           | Datos de los clientes, adjuntos y etiquetas (por ejemplo "créditos abiertos" o "baterías").             |
| **Artículos**                          | Catálogo con categoría, marca, modelo y año. Se puede importar y exportar desde Excel.                  |
| **Créditos / Préstamos**               | Control de créditos con sus artículos o vehículos asociados.                                            |
| **Adjuntos**                           | Subir facturas y otros archivos a un registro (por ejemplo, una salida).                                |
| **Etiquetas**                          | Clasificar registros para encontrarlos más rápido.                                                      |

## Exportar e importar

- Los botones de **Exportar** generan un `.zip` con el Excel y las carpetas de archivos adjuntos.
- Los Excel de entradas incluyen una fila de **total** al final.
- Los botones de **Importar** cargan registros desde un archivo (por ejemplo artículos).

## Reglas de seguridad

- Los adjuntos solo se pueden ver **iniciando sesión y desde la red del taller**.
- Lo que cada persona puede ver o editar depende de su rol y permisos.

## Problemas frecuentes

| Síntoma                                         | Qué hacer                                                                  |
| ----------------------------------------------- | -------------------------------------------------------------------------- |
| Al crear un registro dice que el `id` ya existe | Avisar a IT. Tiene solución rápida (ver Parte 2, sección "Mantenimiento"). |
| `quantum.test` no abre                          | Revisar que estés en la red del taller y avisar a IT si sigue sin abrir.   |
| No veo un módulo o botón                        | Pedir que te ajusten los permisos del rol.                                 |

---

# Parte 2: Para desarrolladores

## Stack

- Laravel + **Filament 4**
- **PostgreSQL** (`pgsql`)
- Python (pandas) solo para tareas de Excel; no es un proyecto aparte y se comunica con Filament
- Livewire (widgets de tablas)
- Filament Shield (permisos)
- spatie/laravel-tags (etiquetas)

## Infraestructura

- VM en VirtualBox con Ubuntu, servida con Apache.
- Acceso por `quantum.test` vía hosts file de Windows y dnsmasq (DNS preferido apuntando a la IP de la VM en las PCs del taller).
- ~10 PCs en la red local.

Pendientes:

- [ ] IP estática de la VM con netplan.
- [ ] Terminar la configuración DNS (dnsmasq) en las PCs restantes.

## Modelos principales

### `outflows` (salidas / gastos)

| Campo        | Tipo                                           |
| ------------ | ---------------------------------------------- |
| invoice_date | date                                           |
| company      | string (texto libre, no hay tabla de empresas) |
| invoice_code | string                                         |
| quantity     | integer                                        |
| amount       | decimal(10,2)                                  |
| description  | text                                           |
| source       | string (default `"Caja"`)                      |
| area         | string                                         |

### `inflows` (entradas / ingresos)

| Campo           | Tipo                               |
| --------------- | ---------------------------------- |
| invoice_number  | string                             |
| date            | date                               |
| description     | text                               |
| customer_id     | FK → `customers`                   |
| amount          | decimal                            |
| payment_method  | enum: `cash`, `transfer`           |
| transfer_number | nullable                           |
| bank_id         | nullable, FK → `banks`             |
| transfer_date   | nullable                           |
| payment_status  | enum: `pending`, `partial`, `paid` |
| salesperson     | string                             |
| notes           | nullable                           |

### `Article`

`category_id` (FK → categories), `brand`, `model`, `year`, `description`. Se importa/exporta con `ArticleImporter` y las acciones de export de Filament.

- `ArticleImporter::resolveRecord()` hace match por la combinación **brand + model + year** (antes usaba un solo campo no único y las filas se sobrescribían).

### Créditos / préstamos (`LoansForm`)

- Repeater de ítems con `item_type` polimórfico (`ArticleUnit` o `Transportation`) e `item_id`.
- `item_id::getSearchResultsUsing()` es **un solo closure** (antes se llamaba dos veces y se pisaba). La búsqueda de vehículos coincide por `vin`, `article.brand` y `article.model`, usando `whereRaw('LOWER(...) LIKE ?', ...)` para evitar problemas de collation sensible a mayúsculas.

### `Attachment` (polimórfico)

- `morphTo attachable`; campos `collection`, `disk`, `path`, `original_name`, `mime_type`, `size`.
- Integrado en `Outflow`; pendiente/en curso: relation manager de adjuntos en Customer (`getRelations()`).

## Adjuntos y seguridad

- Disk privado `attachments` (local, `storage/app/private/attachments`).
- Acceso por ruta firmada `attachments.show` + middleware `EnsureAttachmentAccess`: requiere auth y que la IP esté en el CIDR `192.168.0.0/24` (configurable en `config/attachments.php`), con excepción para loopback.
- Permisos con Filament Shield: hay que correr `shield:generate` y asignar los permisos de `Attachment` al rol.

## Vista de flujo de caja

- Tablas separadas en widgets Livewire: `OutflowsTableWidget` e `InflowsTableWidget` (`HasTable` + `HasSchemas` + `HasActions`), usados en las pestañas de la vista `cash-flow`.
- `ExportAction` e `ImportAction` viven en `getTableHeaderActions()` de cada widget (ya no en `ListOutflows::getHeaderActions()`, porque esa página no renderiza `$this->table`; solo `CreateAction` quedó en la página).
- La URL de `EditAction` y `recordUrl` se define dentro de `OutflowsTable::configure()` / `InflowsTable::configure()`, no en cada widget.

## Exports

- `XlsxDownloader` y `CsvDownloader` custom arman un `.zip` (Excel + carpetas de adjuntos) y comparten `ExportZipService` (`App\Filament\Exports\Services`), inyectado por constructor; bindings en `AppServiceProvider::register()`.
- Los exporters llevan `ExportColumn::make('id')` al final (sin `visible()`, que no existe en Filament 4.11) para mapear adjuntos por registro. `ExportAction` usa `columnMapping(false)` para que esa columna no dependa de selección manual.
- Laravel genera el Excel de inflows; Python solo agrega la fila de total (suma de `amount`). Los temporales van en el storage de Laravel, no en `/tmp`.
- Scripts Python:
  - `python/scripts/add_total.py`: genérico para cualquier columna de monto (inflows, outflows y otros exports).
  - `python/scripts/cash_report.py`: arma el reporte de caja.

Pendiente / deseado:

- [ ] Botón que descargue inflows y outflows en la misma hoja, lado a lado con 2 columnas de separación.
- [ ] Análisis (facturas duplicadas/anómalas, proyecciones desde el histórico) y gráficos (pastel o barras) en los Excel.

## Etiquetas

- spatie/laravel-tags (en vez de una tabla propia), usando `SpatieTagsInput` en el formulario de Customer.
- Pendiente: `TagResource` en Filament para administrar las etiquetas.

## Mantenimiento: error de `id` duplicado

**Síntoma:** al crear un registro, Postgres dice que el `id` (p. ej. `2`) ya existe aunque la tabla tenga muchos registros.

**Causa:** la secuencia del `id` quedó desfasada porque se insertaron registros con `id` explícito (imports, seeders, restore de dump). La secuencia sigue en 1 y choca con los existentes.

**Diagnóstico** (cambiar la tabla):

```bash
php artisan tinker --execute="dump(DB::select(\"SELECT last_value FROM customers_id_seq\"), DB::table('customers')->max('id'));"
```

Si `last_value` < `max(id)`, está desfasada.

**Solución, una tabla:**

```bash
php artisan tinker --execute="DB::statement(\"SELECT setval(pg_get_serial_sequence('customers','id'), COALESCE((SELECT MAX(id) FROM customers),1), (SELECT MAX(id) IS NOT NULL FROM customers))\");"
```

**Solución, todas las tablas** (comando artisan `db:fix-sequences`, clase `App\Console\Commands\FixSequences`):

```bash
php artisan db:fix-sequences
```

Es idempotente. Los `SKIP` de `notifications`, `sessions` y `job_batches` son normales (usan `id` string/uuid, sin secuencia numérica).

**Prevención:** si insertas con `id` explícito (seeders, importers, restores), ejecuta el `setval` al final.

## Convenciones y notas

- Nombres de modelos/campos en inglés (`outflows`, `inflows`, `invoice_date`, etc.).
- En MySQL no aplica el problema de secuencias, pero este proyecto usa PostgreSQL; ojo con la sensibilidad a mayúsculas en `LIKE` (usar `LOWER()` / `ILIKE`).ivo con php y laravel reverb