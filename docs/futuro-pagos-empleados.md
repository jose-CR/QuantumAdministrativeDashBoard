# Futuro: pagos y boletas de los empleados

**No está implementado.** El modelo `Employee` nació sin salario ni pagos a propósito:
son datos sensibles que necesitan histórico, y hoy el taller no los pide. Esto queda escrito
para que la próxima tanda tenga el diseño pensado y no se improvise sobre la tabla.

## Qué existe hoy

`employees` (ver `2026_11_13_120000_create_employees_table.php`): código, nombre, documento,
contacto, puesto, alta/baja, estado, `user_id` opcional y `notes`. Sin montos.

Como el empleado ya existe como registro propio, sumarle pagos después es agregar **una tabla
nueva**, no cambiar la identidad de la persona: nada de lo cargado se rompe.

## Diseño propuesto para las boletas

Tabla `employee_payments` (una fila = una boleta de un período):

| columna | tipo | por qué |
|---|---|---|
| `employee_id` | FK employees | de quién es la boleta |
| `period` | string `YYYY-MM` | el mes que se paga; único por empleado |
| `gross_amount` | decimal(10,2) | devengado: sueldo + extras del período |
| `deductions` | decimal(10,2) | ISSS, AFP, renta y descuentos propios |
| `net_amount` | decimal(10,2) | lo que efectivamente se entrega |
| `payment_method` | string | mismo catálogo que caja (`cash`, `card`, `transfer`, `cheque`) |
| `paid_at` | date nullable | cuándo se pagó; nulo = programada |
| `status` | `pending` / `paid` | derivado de `paid_at`, igual que en las entradas de caja |
| `notes` | text | lo que no entra en ningún campo |
| `user_id` | FK users nullable | quién registró el pago |

Reglas que conviene copiar de lo que ya funciona en caja y créditos:

- **El estado se deriva del pago**, no se elige en un select (`pending` si no hay `paid_at`).
- **Una boleta por empleado y período**: `unique(employee_id, period)` — dos boletas del mismo
  mes es un error de carga, no un caso válido.
- **No se borra una boleta pagada**: si hubo un error, se corrige con otra fila o con un
  ajuste, para que el histórico no mienta.
- **Adjuntos** por la tabla polimórfica que ya existe (`attachments`), como el comprobante de
  una entrada.
- **El total nunca se guarda**: `net_amount` se calcula de `gross_amount - deductions` en el modelo.

## Cuándo conviene hacerlo

Cuando el taller pida control de planilla. Si solo necesita saber **quién vendió qué**, eso no
requiere esta tabla: alcanza con el `employee_id` en las entradas y en los créditos (que es el
paso que sigue a este modelo).
