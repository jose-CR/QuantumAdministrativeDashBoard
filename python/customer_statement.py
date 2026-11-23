"""Estados de cuenta en Excel: una hoja por cliente.

Uso:
    python customer_statement.py <datos.json> <salida.xlsx>

El JSON lo arma App\\Services\\Reports\\CustomerStatementService con los datos de la
base; acá solo se dibuja. El libro trae:

  Resumen    — índice de clientes (con enlace a su hoja) y totales
  Vendedores — cuántos clientes y cuánto debe cada vendedor
  01 Nombre  — una hoja por cliente con todos sus créditos, cuotas y pagos
"""

import json
import os
import sys

from openpyxl import Workbook
from openpyxl.styles import Alignment, Border, Font, PatternFill, Side
from openpyxl.utils import get_column_letter

AZUL = "1F3864"
AZUL_CLARO = "D9E2F3"
GRIS = "F2F2F2"
ROJO = "FCE4E4"
VERDE = "E2EFDA"
AMBAR = "FFF2CC"
BORDE = Side(style="thin", color="BFBFBF")

DINERO = '"$"#,##0.00'
PORCENTAJE = '0.00"%"'

ESTADOS = {
    "active": "Activo",
    "completed": "Cancelado",
    "cancelled": "Anulado",
    "refinanced": "Refinanciado",
    "late": "Atrasado",
    "pending": "Pendiente",
}

PERIODICIDADES = {
    "weekly": "Semanal",
    "biweekly": "Quincenal",
    "monthly": "Mensual",
    "yearly": "Anual",
}

METODOS = {
    "cash": "Efectivo",
    "card": "Tarjeta",
    "transfer": "Transferencia",
    "bank_transfer": "Transferencia",
    "cheque": "Cheque",
    "quedan": "Quedan",
}

CUOTA_ESTADOS = {
    "paid": "Pagada",
    "pending": "Pendiente",
    "late": "Vencida",
    "partial": "Parcial",
    "cancelled": "Anulada",
}


# ---------------------------------------------------------------- utilidades


def banda(hoja, fila, texto, columnas, color=AZUL, alto=20):
    hoja.merge_cells(start_row=fila, start_column=1, end_row=fila, end_column=columnas)
    celda = hoja.cell(row=fila, column=1, value=texto)
    celda.fill = PatternFill("solid", fgColor=color)
    celda.font = Font(bold=True, color="FFFFFF", size=11)
    celda.alignment = Alignment(vertical="center")
    hoja.row_dimensions[fila].height = alto
    return fila + 1


def cabecera_tabla(hoja, fila, titulos):
    for columna, titulo in enumerate(titulos, start=1):
        celda = hoja.cell(row=fila, column=columna, value=titulo)
        celda.fill = PatternFill("solid", fgColor=AZUL_CLARO)
        celda.font = Font(bold=True, size=9)
        celda.border = Border(bottom=BORDE)
        celda.alignment = Alignment(horizontal="center", vertical="center", wrap_text=True)
    hoja.row_dimensions[fila].height = 26
    return fila + 1


def par(hoja, fila, izquierda, valor_izq, derecha=None, valor_der=None, formato=None, formato_der=None):
    celda = hoja.cell(row=fila, column=1, value=izquierda)
    celda.font = Font(bold=True, size=9, color="595959")

    celda = hoja.cell(row=fila, column=2, value=valor_izq)
    celda.alignment = Alignment(horizontal="left")
    if formato:
        celda.number_format = formato

    if derecha:
        celda = hoja.cell(row=fila, column=4, value=derecha)
        celda.font = Font(bold=True, size=9, color="595959")

        celda = hoja.cell(row=fila, column=5, value=valor_der)
        celda.alignment = Alignment(horizontal="left")
        if formato_der:
            celda.number_format = formato_der

    return fila + 1


def texto_metodo(metodo):
    if not metodo:
        return "—"
    return METODOS.get(metodo, metodo.replace("_", " ").capitalize())


def lugar(cliente):
    partes = [cliente.get("municipio"), cliente.get("departamento")]
    partes = [p for p in partes if p and not str(p).isdigit()]
    return ", ".join(partes) or "—"


def nombre_hoja(indice, nombre):
    limpio = (nombre or "Cliente").replace("/", "-").replace("\\", "-").replace("*", "-")
    limpio = limpio.replace("[", "(").replace("]", ")").replace(":", "-").replace("?", "")
    return f"{indice:02d} {limpio}"[:31]


# ---------------------------------------------------------------- hojas


def hoja_resumen(libro, datos):
    hoja = libro.create_sheet("Resumen", 0)
    hoja.sheet_properties.tabColor = "C00000"

    for columna, ancho in {
        "A": 5, "B": 30, "C": 20, "D": 14, "E": 10, "F": 14, "G": 14, "H": 14, "I": 9, "J": 9, "K": 12,
    }.items():
        hoja.column_dimensions[columna].width = ancho

    fila = banda(hoja, 1, "QUANTUM · Estados de cuenta por cliente", 11)
    hoja.cell(row=2, column=1, value=(
        f"Generado el {datos['generado']} · {len(datos['clientes'])} cliente(s) · "
        f"saldo vencido ${sum(c['resumen']['saldo_vencido'] for c in datos['clientes']):,.2f}"
    )).font = Font(size=9, italic=True, color="7F7F7F")

    fila = 4
    fila = cabecera_tabla(hoja, fila, [
        "#", "Cliente", "Documento", "Teléfono", "Créditos", "Total", "Pagado",
        "Saldo", "Cuotas", "Vencidas", "Vendedor",
    ])

    totales = {clave: 0 for clave in ("total", "pagado", "saldo", "cuotas", "vencidas", "saldo_vencido")}

    for indice, cuenta in enumerate(datos["clientes"], start=1):
        cliente = cuenta["cliente"]
        resumen = cuenta["resumen"]

        valores = [
            indice, cliente["nombre"], cliente["documento"], cliente["telefono"],
            resumen["creditos"], resumen["total"], resumen["pagado"], resumen["saldo"],
            resumen["cuotas"], resumen["vencidas"], cuenta.get("vendedor") or "Sin vendedor",
        ]

        for columna, valor in enumerate(valores, start=1):
            celda = hoja.cell(row=fila, column=columna, value=valor)
            celda.border = Border(bottom=BORDE)
            if columna in (6, 7, 8):
                celda.number_format = DINERO
            if columna in (1, 5, 9, 10):
                celda.alignment = Alignment(horizontal="center")
            if indice % 2 == 0:
                celda.fill = PatternFill("solid", fgColor=GRIS)

        enlace = hoja.cell(row=fila, column=2)
        enlace.hyperlink = f"#'{nombre_hoja(indice, cliente['nombre'])}'!A1"
        enlace.font = Font(color="0563C1", underline="single", bold=True)

        if resumen["vencidas"]:
            hoja.cell(row=fila, column=10).font = Font(bold=True, color="C00000")

        for clave in totales:
            totales[clave] += resumen[clave]

        fila += 1

    hoja.cell(row=fila, column=2, value="TOTAL").font = Font(bold=True)
    for columna, clave in ((6, "total"), (7, "pagado"), (8, "saldo")):
        celda = hoja.cell(row=fila, column=columna, value=round(totales[clave], 2))
        celda.font = Font(bold=True)
        celda.number_format = DINERO
    for columna, clave in ((9, "cuotas"), (10, "vencidas")):
        celda = hoja.cell(row=fila, column=columna, value=totales[clave])
        celda.font = Font(bold=True, color="C00000" if clave == "vencidas" else "000000")
        celda.alignment = Alignment(horizontal="center")

    hoja.freeze_panes = "A5"
    hoja.auto_filter.ref = f"A4:K{fila - 1}"

    return hoja


def hoja_vendedores(libro, datos):
    hoja = libro.create_sheet("Vendedores", 1)
    hoja.sheet_properties.tabColor = "7030A0"

    for columna, ancho in {"A": 28, "B": 11, "C": 11, "D": 15, "E": 15, "F": 15, "G": 11, "H": 15}.items():
        hoja.column_dimensions[columna].width = ancho

    fila = banda(hoja, 1, "QUANTUM · Resumen por vendedor", 8, color="7030A0")
    hoja.cell(row=2, column=1, value=f"Generado el {datos['generado']}").font = Font(
        size=9, italic=True, color="7F7F7F"
    )

    fila = 4
    fila = cabecera_tabla(hoja, fila, [
        "Vendedor", "Clientes", "Créditos", "Total", "Pagado", "Saldo", "Vencidas", "Saldo vencido",
    ])

    totales = {"clientes": 0, "creditos": 0, "total": 0.0, "pagado": 0.0, "saldo": 0.0,
               "vencidas": 0, "saldo_vencido": 0.0}

    for posicion, vendedor in enumerate(datos.get("vendedores", []), start=1):
        valores = [
            vendedor["vendedor"], vendedor["clientes"], vendedor["creditos"], vendedor["total"],
            vendedor["pagado"], vendedor["saldo"], vendedor["vencidas"], vendedor["saldo_vencido"],
        ]
        for columna, valor in enumerate(valores, start=1):
            celda = hoja.cell(row=fila, column=columna, value=valor)
            celda.border = Border(bottom=BORDE)
            if columna in (4, 5, 6, 8):
                celda.number_format = DINERO
            if columna in (2, 3, 7):
                celda.alignment = Alignment(horizontal="center")
            if posicion % 2 == 0:
                celda.fill = PatternFill("solid", fgColor=GRIS)

        for clave in totales:
            totales[clave] += vendedor[clave]

        fila += 1

    hoja.cell(row=fila, column=1, value="TOTAL").font = Font(bold=True)
    for columna, clave in ((2, "clientes"), (3, "creditos"), (4, "total"), (5, "pagado"),
                           (6, "saldo"), (7, "vencidas"), (8, "saldo_vencido")):
        valor = totales[clave] if clave in ("clientes", "creditos", "vencidas") else round(totales[clave], 2)
        celda = hoja.cell(row=fila, column=columna, value=valor)
        celda.font = Font(bold=True)
        if columna >= 4:
            celda.number_format = DINERO

    hoja.freeze_panes = "A5"

    return hoja


def hoja_cliente(libro, indice, cuenta):
    cliente = cuenta["cliente"]
    resumen = cuenta["resumen"]

    hoja = libro.create_sheet(nombre_hoja(indice, cliente["nombre"]))
    hoja.sheet_properties.tabColor = AZUL

    for columna, ancho in {"A": 18, "B": 16, "C": 13, "D": 16, "E": 14, "F": 12, "G": 12}.items():
        hoja.column_dimensions[columna].width = ancho

    fila = banda(hoja, 1, f"QUANTUM · Estado de cuenta — {cliente['nombre']}", 7)
    hoja.cell(row=2, column=1, value=f"Generado el {libro.generado}").font = Font(
        size=9, italic=True, color="7F7F7F"
    )
    fila = 4

    # ---- Datos del cliente
    fila = banda(hoja, fila, "DATOS DEL CLIENTE", 7)
    fila = par(hoja, fila, "Nombre", cliente["nombre"], "Documento", cliente["documento"])
    telefono = cliente["telefono"]
    if cliente.get("telefono_alt"):
        telefono = f"{telefono} / {cliente['telefono_alt']}"
    fila = par(hoja, fila, "Teléfono", telefono, "Correo", cliente["correo"])
    fila = par(hoja, fila, "Dirección", cliente["direccion"], "Ubicación", lugar(cliente))
    fila = par(hoja, fila, "Etiquetas", ", ".join(cliente["etiquetas"]) or "Sin etiqueta",
               "Vendedor", cuenta.get("vendedor") or "Sin vendedor")
    fila += 1

    # ---- Resumen de la persona (todos sus créditos)
    fila = banda(hoja, fila, f"RESUMEN DEL CLIENTE · {resumen['creditos']} crédito(s)", 7)
    fila = par(hoja, fila, "Total a pagar", resumen["total"], "Total pagado", resumen["pagado"],
               formato=DINERO, formato_der=DINERO)
    fila = par(hoja, fila, "Saldo pendiente", resumen["saldo"], "Cuotas pagadas",
               f"{resumen['cuotas_pagadas']} de {resumen['cuotas']}", formato=DINERO)
    fila = par(hoja, fila, "Cuotas vencidas", resumen["vencidas"], "Saldo vencido", resumen["saldo_vencido"],
               formato_der=DINERO)
    fila += 1

    # ---- Un bloque por crédito
    for numero, bloque in enumerate(cuenta["creditos"], start=1):
        credito = bloque["credito"]
        resumen_credito = bloque["resumen"]

        fila = banda(hoja, fila, f"CRÉDITO #{credito['id']}", 7)
        fila = par(hoja, fila, "Estado", ESTADOS.get(credito["estado"], credito["estado"] or "—"),
                   "Fecha de inicio", credito["inicio"] or "—")
        fila = par(hoja, fila, "Monto inicial", credito["monto_inicial"], "Prima", credito["prima"],
                   formato=DINERO, formato_der=DINERO)
        fila = par(hoja, fila, "Monto financiado", credito["financiado"], "Total a pagar", credito["total"],
                   formato=DINERO, formato_der=DINERO)
        fila = par(hoja, fila, "Cuotas", credito["cuotas"], "Monto por cuota", credito["cuota_monto"],
                   formato_der=DINERO)
        fila = par(hoja, fila, "Tasa de interés", credito["tasa"], "Interés total", credito["interes"],
                   formato=PORCENTAJE, formato_der=DINERO)
        fila = par(hoja, fila, "Periodicidad",
                   PERIODICIDADES.get(credito["periodicidad"], credito["periodicidad"] or "—"),
                   "Día de pago", credito["dia_pago"] or "—")
        fila = par(hoja, fila, "Saldo pendiente", credito["saldo"], "Cuotas vencidas", resumen_credito["vencidas"],
                   formato=DINERO)

        if bloque["articulos"]:
            for articulo in bloque["articulos"]:
                hoja.cell(row=fila, column=1, value=f"{articulo['cantidad']} ×").font = Font(bold=True, size=9)
                hoja.cell(row=fila, column=2, value=articulo["descripcion"]).alignment = Alignment(horizontal="left")
                celda = hoja.cell(row=fila, column=5, value=articulo["precio"])
                celda.number_format = DINERO
                fila += 1

        fila += 1

        # Cuotas
        fila = cabecera_tabla(hoja, fila, [
            "Cuota N°", "Vence", "Monto", "Pagado", "Saldo", "Estado", "Días de atraso",
        ])

        for cuota in bloque["cuotas"]:
            valores = [
                cuota["numero"], cuota["vence"], cuota["monto"], cuota["pagado"],
                cuota["saldo"], CUOTA_ESTADOS.get(cuota["estado"], cuota["estado"]),
                cuota["dias_atraso"] or "—",
            ]
            relleno = None
            if cuota["dias_atraso"] > 0:
                relleno = ROJO
            elif cuota["estado"] == "paid":
                relleno = VERDE
            elif cuota["estado"] == "partial":
                relleno = AMBAR

            for columna, valor in enumerate(valores, start=1):
                celda = hoja.cell(row=fila, column=columna, value=valor)
                celda.border = Border(bottom=BORDE)
                if columna in (3, 4, 5):
                    celda.number_format = DINERO
                if columna in (1, 6, 7):
                    celda.alignment = Alignment(horizontal="center")
                if relleno:
                    celda.fill = PatternFill("solid", fgColor=relleno)
            fila += 1

        hoja.cell(row=fila, column=1, value="TOTAL").font = Font(bold=True, size=9)
        for columna, valor in ((3, sum(c["monto"] for c in bloque["cuotas"])),
                               (4, sum(c["pagado"] for c in bloque["cuotas"])),
                               (5, sum(c["saldo"] for c in bloque["cuotas"]))):
            celda = hoja.cell(row=fila, column=columna, value=valor)
            celda.font = Font(bold=True, size=9)
            celda.number_format = DINERO
        fila += 2

        # Pagos
        if bloque["pagos"]:
            fila = cabecera_tabla(hoja, fila, [
                "Fecha", "Monto", "Método", "Banco / nota", "Comprobante", "Registró", "",
            ])
            for pago in bloque["pagos"]:
                detalle = pago["banco"] or ""
                if pago["nota"]:
                    detalle = (detalle + " · " if detalle else "") + pago["nota"]

                valores = [
                    pago["fecha"], pago["monto"], texto_metodo(pago["metodo"]),
                    detalle[:60], pago["comprobante"] or "—", pago["usuario"] or "—",
                ]
                for columna, valor in enumerate(valores, start=1):
                    celda = hoja.cell(row=fila, column=columna, value=valor)
                    celda.border = Border(bottom=BORDE)
                    if columna == 2:
                        celda.number_format = DINERO
                fila += 1

            hoja.cell(row=fila, column=1, value="TOTAL").font = Font(bold=True, size=9)
            celda = hoja.cell(row=fila, column=2, value=sum(p["monto"] for p in bloque["pagos"]))
            celda.font = Font(bold=True, size=9)
            celda.number_format = DINERO
            hoja.cell(row=fila, column=3, value=f"{len(bloque['pagos'])} pago(s)").font = Font(
                size=9, italic=True
            )
            fila += 1
        else:
            hoja.cell(row=fila, column=1, value="Este crédito no tiene pagos registrados.").font = Font(
                size=9, italic=True, color="7F7F7F"
            )
            fila += 1

        fila += 1

    hoja.freeze_panes = "A3"
    hoja.page_setup.orientation = "portrait"
    hoja.page_setup.fitToWidth = 1
    hoja.sheet_properties.pageSetUpPr.fitToPage = True

    return hoja


def main():
    if len(sys.argv) < 3:
        print("uso: customer_statement.py <datos.json> <salida.xlsx>", file=sys.stderr)
        return 1

    origen, destino = sys.argv[1], sys.argv[2]

    with open(origen, encoding="utf-8") as archivo:
        datos = json.load(archivo)

    libro = Workbook()
    libro.remove(libro.active)
    libro.generado = datos.get("generado", "")

    # Resumen y Vendedores primero: son el índice, van al principio del libro.
    hoja_resumen(libro, datos)
    hoja_vendedores(libro, datos)

    for indice, cuenta in enumerate(datos["clientes"], start=1):
        hoja_cliente(libro, indice, cuenta)

    os.makedirs(os.path.dirname(destino) or ".", exist_ok=True)
    libro.save(destino)

    print(destino)
    return 0


if __name__ == "__main__":
    sys.exit(main())
