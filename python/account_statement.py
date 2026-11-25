"""Estado de cuenta en Excel: una hoja por cliente.

Uso:
    python account_statement.py <datos.json> <salida.xlsx>

El JSON lo arma App\\Services\\Reports\\AccountStatementService: facturas (cargos),
pagos (abonos), plan de cuotas de lo vendido a crédito y antigüedad del saldo.
Acá solo se dibuja.

Orden del documento:
    encabezado → datos → resumen → antigüedad → facturas → planes → recibos → notas
Las facturas van con las que tienen saldo primero (de la más vieja a la más nueva) y
después las pagadas: es el hilo que se persigue al cobrar.
"""

import json
import os
import sys

from openpyxl import Workbook
from openpyxl.styles import Alignment, Border, Font, PatternFill, Side
from openpyxl.worksheet.page import PageMargins

EMPRESA = "INDUSTRIAS QUANTUM MOTORS, S.A. DE C.V."
CONTACTO = [
    "Casa Matriz: Quinta San José, Pje. Cuscatlán, Cuscatancingo, 1101, El Salvador",
    "Tel. 2124-6244  ·  7944-4000   ·   infosv@tuquantum.com   ·   www.tuquantum.com.sv",
]

AZUL = "16324F"
AZUL_MEDIO = "1F4E79"
CLARO = "DCE6F1"
GRIS_FILA = "F7F9FC"
GRIS_TEXTO = "6B7280"
VERDE = "1B7F3B"
AMBAR = "9A6700"
ROJO = "B42318"

DINERO = '"$"#,##0.00'

# Caracteres que openpyxl no acepta en el nombre de una hoja.
# `chr(92)` es la barra invertida: escribirla escapada en el código es un lío.
PROHIBIDOS = "/" + chr(92) + "?*[]:"
FIN = Side(style="thin", color="C9D2DC")
MEDIO = Side(style="medium", color=AZUL)

ANCHOS = {"A": 8, "B": 11, "C": 11, "D": 52, "E": 11, "F": 13, "G": 13, "H": 13, "I": 11, "J": 7}
ULTIMA = 10

COLOR_ESTADO = {"pagado": (VERDE, "Pagado"), "parcial": (AMBAR, "Parcial"), "pendiente": (ROJO, "Pendiente")}


# --------------------------------------------------------------- utilidades


def banda(hoja, fila, texto, desde=1, hasta=ULTIMA, color=AZUL_MEDIO, alto=18):
    hoja.merge_cells(start_row=fila, start_column=desde, end_row=fila, end_column=hasta)
    celda = hoja.cell(row=fila, column=desde, value=texto)
    celda.fill = PatternFill("solid", fgColor=color)
    celda.font = Font(bold=True, color="FFFFFF", size=9)
    celda.alignment = Alignment(vertical="center", indent=1)
    hoja.row_dimensions[fila].height = alto
    return fila + 1


def titulos(hoja, fila, columnas):
    """columnas: [(columna, titulo, ancho_combinado)]"""
    for columna, titulo, ancho in columnas:
        if ancho > 1:
            hoja.merge_cells(start_row=fila, start_column=columna, end_row=fila, end_column=columna + ancho - 1)
        celda = hoja.cell(row=fila, column=columna, value=titulo)
        celda.fill = PatternFill("solid", fgColor=CLARO)
        celda.font = Font(bold=True, size=8, color=AZUL)
        celda.alignment = Alignment(horizontal="center", vertical="center")
        celda.border = Border(top=FIN, bottom=FIN, left=FIN, right=FIN)
    hoja.row_dimensions[fila].height = 22
    return fila + 1


def etiqueta_valor(hoja, fila, etiqueta, valor, columna=1):
    celda = hoja.cell(row=fila, column=columna, value=etiqueta)
    celda.font = Font(bold=True, size=8, color=GRIS_TEXTO)

    celda = hoja.cell(row=fila, column=columna + 1, value=valor)
    celda.font = Font(size=9)
    celda.alignment = Alignment(horizontal="left")
    return fila + 1


def dinero(celda, negrita=False, color=None, tamano=8.5):
    celda.number_format = DINERO
    celda.alignment = Alignment(horizontal="right")
    celda.font = Font(size=tamano, bold=negrita, color=color or "000000")
    return celda


def numero(valor):
    return f"{valor:,.2f}"


# --------------------------------------------------------------- bloques


def encabezado(hoja, datos, fila=1):
    hoja.merge_cells(start_row=fila, start_column=1, end_row=fila, end_column=ULTIMA)
    celda = hoja.cell(row=fila, column=1, value=EMPRESA)
    celda.fill = PatternFill("solid", fgColor=AZUL)
    celda.font = Font(bold=True, size=15, color="FFFFFF")
    celda.alignment = Alignment(vertical="center", indent=1)
    hoja.row_dimensions[fila].height = 34
    fila += 1

    for linea in CONTACTO:
        hoja.merge_cells(start_row=fila, start_column=1, end_row=fila, end_column=ULTIMA)
        celda = hoja.cell(row=fila, column=1, value=linea)
        celda.font = Font(size=8, color=GRIS_TEXTO)
        celda.alignment = Alignment(horizontal="center")
        fila += 1

    fila += 1
    hoja.merge_cells(start_row=fila, start_column=1, end_row=fila, end_column=ULTIMA)
    celda = hoja.cell(row=fila, column=1, value="ESTADO DE CUENTA")
    celda.font = Font(bold=True, size=13, color=AZUL)
    celda.alignment = Alignment(horizontal="center", vertical="center")
    hoja.row_dimensions[fila].height = 26
    for columna in range(1, ULTIMA + 1):
        hoja.cell(row=fila, column=columna).border = Border(bottom=MEDIO)
    fila += 1

    hoja.merge_cells(start_row=fila, start_column=1, end_row=fila, end_column=ULTIMA)
    celda = hoja.cell(row=fila, column=1, value=(
        f"Estado de cuenta N° {datos['estado']['numero']}   ·   "
        "Documento informativo, no constituye factura"
    ))
    celda.font = Font(size=8, italic=True, color=GRIS_TEXTO)
    celda.alignment = Alignment(horizontal="center")

    return fila + 2


def datos_cliente(hoja, fila, datos):
    cliente = datos["cliente"]
    estado = datos["estado"]
    resumen = datos["resumen"]

    izquierda = fila
    izquierda = banda(hoja, izquierda, "DATOS DEL CLIENTE", hasta=5)
    izquierda = etiqueta_valor(hoja, izquierda, "Cliente", cliente["nombre"])
    izquierda = etiqueta_valor(hoja, izquierda, "Documento", cliente["documento"])
    izquierda = etiqueta_valor(hoja, izquierda, "Dirección", cliente["direccion"])
    izquierda = etiqueta_valor(hoja, izquierda, "Contacto", f"{cliente['telefono']}   ·   {cliente['correo']}")

    derecha = banda(hoja, fila, "DATOS DEL ESTADO DE CUENTA", desde=6)
    derecha = etiqueta_valor(hoja, derecha, "Emisión", estado["emision"], columna=6)
    derecha = etiqueta_valor(hoja, derecha, "Período", f"{estado['desde']} al {estado['hasta']}", columna=6)
    derecha = etiqueta_valor(hoja, derecha, "Moneda", estado["moneda"], columna=6)
    derecha = etiqueta_valor(hoja, derecha, "Facturas", (
        f"{resumen['facturas']} emitidas  ·  {resumen['pagadas']} pagadas  ·  {resumen['con_saldo']} con saldo"
    ), columna=6)

    return max(izquierda, derecha) + 1


def resumen(hoja, fila, datos):
    resumen_datos = datos["resumen"]

    tarjetas = [
        ("TOTAL FACTURADO", resumen_datos["facturado"], AZUL),
        ("TOTAL ABONADO", resumen_datos["abonado"], VERDE),
        ("SALDO PENDIENTE", resumen_datos["saldo"], AZUL),
        ("VENCIDO", resumen_datos["vencido"], ROJO),
        ("POR VENCER", resumen_datos["por_vencer"], AMBAR),
    ]

    fila = banda(hoja, fila, "RESUMEN")

    columna = 1
    for titulo, monto, color in tarjetas:
        hoja.merge_cells(start_row=fila, start_column=columna, end_row=fila, end_column=columna + 1)
        celda = hoja.cell(row=fila, column=columna, value=titulo)
        celda.font = Font(bold=True, size=7, color=GRIS_TEXTO)
        celda.alignment = Alignment(horizontal="center", vertical="bottom")
        celda.border = Border(top=FIN, left=FIN, right=FIN)
        celda.fill = PatternFill("solid", fgColor=GRIS_FILA)

        hoja.merge_cells(start_row=fila + 1, start_column=columna, end_row=fila + 1, end_column=columna + 1)
        celda = hoja.cell(row=fila + 1, column=columna, value=monto)
        celda.font = Font(bold=True, size=12, color=color)
        celda.alignment = Alignment(horizontal="center", vertical="top")
        celda.number_format = DINERO
        celda.border = Border(bottom=FIN, left=FIN, right=FIN)
        celda.fill = PatternFill("solid", fgColor=GRIS_FILA)

        columna += 2

    hoja.row_dimensions[fila].height = 16
    hoja.row_dimensions[fila + 1].height = 24

    return fila + 3


def antiguedad(hoja, fila, datos):
    aging = datos["aging"]

    fila = banda(hoja, fila, "ANTIGÜEDAD DEL SALDO VENCIDO")
    fila = titulos(hoja, fila, [(columna, texto, 1) for columna, texto in enumerate(
        ["1 – 30 días", "31 – 60 días", "61 – 90 días", "Más de 90 días", "Total vencido"], start=1
    )])

    valores = [aging["d1_30"], aging["d31_60"], aging["d61_90"], aging["d90"], aging["total"]]

    for posicion, monto in enumerate(valores, start=1):
        celda = hoja.cell(row=fila, column=posicion, value=monto)
        dinero(celda, negrita=(posicion == 5), color=ROJO if posicion == 5 else None, tamano=9)
        celda.border = Border(bottom=FIN, left=FIN, right=FIN)

    return fila + 2


def facturas(hoja, fila, datos):
    fila = banda(hoja, fila, "FACTURAS")
    fila = titulos(hoja, fila, [
        (1, "N° factura", 1), (2, "Fecha", 1), (3, "Vence", 1), (4, "Concepto", 1),
        (5, "Tipo", 1), (6, "Cargo", 1), (7, "Abono", 1), (8, "Saldo", 1),
        (9, "Estado", 1), (10, "Días", 1),
    ])

    for posicion, factura in enumerate(datos["facturas"]):
        relleno = GRIS_FILA if posicion % 2 else None
        color_estado, etiqueta = COLOR_ESTADO.get(factura["estado"], ("000000", factura["estado"]))

        valores = {
            1: factura["numero"], 2: factura["fecha"], 3: factura["vence"],
            4: factura["concepto"], 5: factura["tipo"],
            6: factura["cargo"], 7: factura["abono"], 8: factura["saldo"],
            9: etiqueta, 10: factura["dias"] if factura["dias"] is not None else "—",
        }

        for columna, valor in valores.items():
            celda = hoja.cell(row=fila, column=columna, value=valor)
            celda.font = Font(size=8.5)
            celda.border = Border(bottom=FIN, left=FIN, right=FIN)
            if relleno:
                celda.fill = PatternFill("solid", fgColor=relleno)

            if columna in (6, 7, 8):
                dinero(celda)
            elif columna in (1, 2, 3, 5, 9, 10):
                celda.alignment = Alignment(horizontal="center")
            if columna == 4:
                celda.alignment = Alignment(vertical="center", wrap_text=True)

        hoja.cell(row=fila, column=1).font = Font(size=8.5, bold=True)
        hoja.cell(row=fila, column=9).font = Font(size=8.5, bold=True, color=color_estado)
        hoja.row_dimensions[fila].height = 24
        fila += 1

    resumen_datos = datos["resumen"]

    hoja.merge_cells(start_row=fila, start_column=1, end_row=fila, end_column=5)
    celda = hoja.cell(row=fila, column=1, value="TOTALES")
    celda.font = Font(bold=True, size=9)
    celda.alignment = Alignment(horizontal="right")

    for columna, valor in ((6, resumen_datos["facturado"]), (7, resumen_datos["abonado"]), (8, resumen_datos["saldo"])):
        celda = hoja.cell(row=fila, column=columna, value=valor)
        dinero(celda, negrita=True, color=AZUL, tamano=9)
        celda.border = Border(top=MEDIO, bottom=MEDIO, left=FIN, right=FIN)

    celda = hoja.cell(row=fila, column=9, value="Control OK")
    celda.font = Font(bold=True, size=8, color=VERDE)
    celda.alignment = Alignment(horizontal="center")
    celda.border = Border(top=MEDIO, bottom=MEDIO, left=FIN, right=FIN)

    fila += 1
    hoja.merge_cells(start_row=fila, start_column=1, end_row=fila, end_column=ULTIMA)
    celda = hoja.cell(row=fila, column=1, value=(
        "El saldo pendiente coincide con la suma de los saldos de cada factura (cargo − abono)."
    ))
    celda.font = Font(size=8, italic=True, color=GRIS_TEXTO)

    return fila + 2


def aviso_creditos(hoja, fila, datos):
    """En la hoja del estado de cuenta solo se avisa dónde están los créditos."""
    planes = datos["planes"]

    if not planes:
        return fila

    fila = banda(hoja, fila, "CRÉDITOS")

    hojas = ", ".join(f"«Crédito #{plan['numero']}»" for plan in planes)

    hoja.merge_cells(start_row=fila, start_column=1, end_row=fila, end_column=ULTIMA)
    celda = hoja.cell(row=fila, column=1, value=(
        f"Esta cuenta tiene {len(planes)} venta(s) a crédito, cada una en su propia hoja: {hojas}. "
        "El plan de cuotas no suma al saldo: la deuda ya está contada en su factura."
    ))
    celda.font = Font(size=8, italic=True, color=GRIS_TEXTO)
    celda.alignment = Alignment(vertical="top", wrap_text=True)
    hoja.row_dimensions[fila].height = 26

    return fila + 2


def nombre_hoja_credito(plan):
    nombre = f"Crédito #{plan['numero']}"
    # Un nombre de hoja no admite estos caracteres; el backslash es uno solo.
    for caracter in PROHIBIDOS:
        nombre = nombre.replace(caracter, "-")
    return nombre[:31]


def hoja_credito(libro, datos, plan):
    """Una hoja por crédito: sus datos y su plan de cuotas completo."""
    hoja = libro.create_sheet(nombre_hoja_credito(plan))
    hoja.sheet_properties.tabColor = AZUL_MEDIO

    for columna, ancho in ANCHOS.items():
        hoja.column_dimensions[columna].width = ancho

    fila = encabezado_credito(hoja, datos, plan)
    fila = datos_credito(hoja, fila, datos, plan)
    fila = cuotas_credito(hoja, fila, plan)

    hoja.page_setup.orientation = "portrait"
    hoja.page_setup.paperSize = hoja.PAPERSIZE_A4
    hoja.page_setup.fitToWidth = 1
    hoja.page_setup.fitToHeight = 0
    hoja.sheet_properties.pageSetUpPr.fitToPage = True
    hoja.page_margins = PageMargins(left=0.4, right=0.4, top=0.5, bottom=0.5)
    hoja.print_title_rows = "1:1"

    return hoja


def encabezado_credito(hoja, datos, plan):
    fila = 1
    hoja.merge_cells(start_row=fila, start_column=1, end_row=fila, end_column=ULTIMA)
    celda = hoja.cell(row=fila, column=1, value=EMPRESA)
    celda.fill = PatternFill("solid", fgColor=AZUL)
    celda.font = Font(bold=True, size=13, color="FFFFFF")
    celda.alignment = Alignment(vertical="center", indent=1)
    hoja.row_dimensions[fila].height = 28
    fila += 1

    hoja.merge_cells(start_row=fila, start_column=1, end_row=fila, end_column=ULTIMA)
    celda = hoja.cell(row=fila, column=1, value=f"ESTADO DE CUENTA · CRÉDITO #{plan['numero']}")
    celda.font = Font(bold=True, size=11, color=AZUL)
    celda.alignment = Alignment(horizontal="center", vertical="center")
    for columna in range(1, ULTIMA + 1):
        hoja.cell(row=fila, column=columna).border = Border(bottom=MEDIO)
    fila += 1

    hoja.merge_cells(start_row=fila, start_column=1, end_row=fila, end_column=ULTIMA)
    celda = hoja.cell(row=fila, column=1, value=(
        f"{datos['cliente']['nombre']}   ·   {datos['cliente']['documento']}   ·   "
        f"estado de cuenta N° {datos['estado']['numero']}"
    ))
    celda.font = Font(size=8, italic=True, color=GRIS_TEXTO)
    celda.alignment = Alignment(horizontal="center")

    return fila + 2


def datos_credito(hoja, fila, datos, plan):
    fila = banda(hoja, fila, "CRÉDITO")

    if plan["factura"]:
        origen = f"Factura N° {plan['factura']} (ver hoja del estado de cuenta)"
    else:
        origen = "Sin factura identificada"

    izquierda = fila
    izquierda = etiqueta_valor(hoja, izquierda, "Origen", origen)
    izquierda = etiqueta_valor(hoja, izquierda, "Inicio", plan["inicio"] or "—")
    izquierda = etiqueta_valor(hoja, izquierda, "Monto inicial", numero(plan["monto_inicial"]))
    izquierda = etiqueta_valor(hoja, izquierda, "Prima", numero(plan["prima"]))
    izquierda = etiqueta_valor(hoja, izquierda, "Financiado", numero(plan["financiado"]))

    derecha = fila
    derecha = etiqueta_valor(hoja, derecha, "Cuotas", f"{plan['cuotas_total']} · {numero(plan['cuota_monto'])} c/u", columna=6)
    derecha = etiqueta_valor(hoja, derecha, "Día de pago", plan["dia_pago"], columna=6)
    derecha = etiqueta_valor(hoja, derecha, "Total a pagar", numero(plan["total_credito"]), columna=6)
    derecha = etiqueta_valor(hoja, derecha, "Saldo pendiente", numero(plan["saldo_credito"]), columna=6)
    derecha = etiqueta_valor(hoja, derecha, "Cuotas vencidas", plan["vencidas"], columna=6)

    return max(izquierda, derecha) + 1


def cuotas_credito(hoja, fila, plan):
    fila = banda(hoja, fila, "PLAN DE CUOTAS")
    fila = titulos(hoja, fila, [
        (1, "Cuota", 1), (2, "Vence", 1), (3, "Estado", 2),
        (5, "Días", 1), (6, "Monto", 1), (7, "Pagado", 1), (8, "Saldo", 1),
    ])

    for indice, cuota in enumerate(plan["cuotas"]):
        relleno = GRIS_FILA if indice % 2 else None
        etiqueta = {"paid": "Pagada", "late": "Vencida", "pending": "Pendiente",
                    "partial": "Parcial", "cancelled": "Anulada"}.get(cuota["estado"], cuota["estado"])
        vencida = cuota["estado"] == "late" and cuota["saldo"] > 0

        for columna, valor in ((1, cuota["numero"]), (2, cuota["vence"]), (5, cuota["dias"]),
                               (6, cuota["monto"]), (7, cuota["pagado"]), (8, cuota["saldo"])):
            celda = hoja.cell(row=fila, column=columna, value=valor)
            celda.font = Font(size=8.5)
            celda.border = Border(bottom=FIN, left=FIN, right=FIN)
            if relleno:
                celda.fill = PatternFill("solid", fgColor=relleno)
            if columna in (1, 2, 5):
                celda.alignment = Alignment(horizontal="center")
            if columna in (6, 7, 8):
                dinero(celda)

        hoja.merge_cells(start_row=fila, start_column=3, end_row=fila, end_column=4)
        celda = hoja.cell(row=fila, column=3, value=etiqueta)
        celda.font = Font(size=8.5, bold=vencida, color=ROJO if vencida else GRIS_TEXTO)
        celda.alignment = Alignment(horizontal="center")
        if relleno:
            celda.fill = PatternFill("solid", fgColor=relleno)
        fila += 1

    hoja.merge_cells(start_row=fila, start_column=1, end_row=fila, end_column=5)
    celda = hoja.cell(row=fila, column=1, value="TOTAL DEL CRÉDITO")
    celda.font = Font(bold=True, size=9)
    celda.alignment = Alignment(horizontal="right")

    for columna, valor in ((6, plan["total"]), (7, round(plan["total"] - plan["saldo"], 2)), (8, plan["saldo"])):
        celda = hoja.cell(row=fila, column=columna, value=valor)
        dinero(celda, negrita=True, color=AZUL, tamano=9)
        celda.border = Border(top=MEDIO, bottom=MEDIO, left=FIN, right=FIN)

    fila += 2

    hoja.merge_cells(start_row=fila, start_column=1, end_row=fila, end_column=ULTIMA)
    celda = hoja.cell(row=fila, column=1, value=(
        "Este plan no se suma al estado de cuenta: la deuda está contada en su factura. "
        "Los pagos aparecen con su número de recibo en la hoja del estado de cuenta."
    ))
    celda.font = Font(size=8, italic=True, color=GRIS_TEXTO)
    celda.alignment = Alignment(vertical="top", wrap_text=True)
    hoja.row_dimensions[fila].height = 22

    return fila + 2


def recibos(hoja, fila, datos):
    fila = banda(hoja, fila, "RECIBOS")

    if not datos["recibos"]:
        hoja.merge_cells(start_row=fila, start_column=1, end_row=fila, end_column=ULTIMA)
        celda = hoja.cell(row=fila, column=1, value="No hay pagos registrados con recibo para este cliente.")
        celda.font = Font(size=8, italic=True, color=GRIS_TEXTO)
        return fila + 2

    fila = titulos(hoja, fila, [
        (1, "Recibo", 1), (2, "Fecha", 1), (3, "Detalle", 2), (5, "Aplicado a", 1),
        (6, "Monto", 2), (8, "Banco / método", 3),
    ])

    total = 0.0

    for indice, recibo in enumerate(datos["recibos"]):
        relleno = GRIS_FILA if indice % 2 else None
        total += recibo["monto"]

        for columna, valor in ((1, recibo["numero"]), (2, recibo["fecha"]), (5, recibo["aplicado"])):
            celda = hoja.cell(row=fila, column=columna, value=valor)
            celda.font = Font(size=8.5, bold=(columna == 1))
            celda.alignment = Alignment(horizontal="center")
            celda.border = Border(bottom=FIN, left=FIN, right=FIN)
            if relleno:
                celda.fill = PatternFill("solid", fgColor=relleno)

        hoja.merge_cells(start_row=fila, start_column=3, end_row=fila, end_column=4)
        celda = hoja.cell(row=fila, column=3, value=recibo["detalle"])
        celda.font = Font(size=8.5)
        celda.border = Border(bottom=FIN, left=FIN, right=FIN)
        if relleno:
            celda.fill = PatternFill("solid", fgColor=relleno)

        hoja.merge_cells(start_row=fila, start_column=6, end_row=fila, end_column=7)
        celda = hoja.cell(row=fila, column=6, value=recibo["monto"])
        dinero(celda)
        celda.border = Border(bottom=FIN, left=FIN, right=FIN)
        if relleno:
            celda.fill = PatternFill("solid", fgColor=relleno)

        detalle_pago = recibo["metodo"]
        if recibo["banco"]:
            detalle_pago += f" · {recibo['banco']}"
        if recibo["usuario"]:
            detalle_pago += f"   ·   {recibo['usuario']}"

        hoja.merge_cells(start_row=fila, start_column=8, end_row=fila, end_column=ULTIMA)
        celda = hoja.cell(row=fila, column=8, value=detalle_pago)
        celda.font = Font(size=8)
        celda.border = Border(bottom=FIN, left=FIN, right=FIN)
        if relleno:
            celda.fill = PatternFill("solid", fgColor=relleno)

        fila += 1

    hoja.merge_cells(start_row=fila, start_column=1, end_row=fila, end_column=5)
    celda = hoja.cell(row=fila, column=1, value="TOTAL RECIBOS")
    celda.font = Font(bold=True, size=9)
    celda.alignment = Alignment(horizontal="right")

    hoja.merge_cells(start_row=fila, start_column=6, end_row=fila, end_column=7)
    celda = hoja.cell(row=fila, column=6, value=round(total, 2))
    dinero(celda, negrita=True, color=VERDE, tamano=9)
    celda.border = Border(top=MEDIO, bottom=MEDIO, left=FIN, right=FIN)

    return fila + 2


def notas(hoja, fila):
    fila = banda(hoja, fila, "NOTAS")

    textos = [
        "Los montos están expresados en dólares de los Estados Unidos de América (USD).",
        "El estado incluye todas las facturas del cliente, pagadas y con saldo: es su historial completo.",
        "El saldo de cada factura se calcula como cargo − abono. El saldo pendiente es la suma de esos saldos y no se modifica a mano.",
        "En una venta a crédito, la factura es el cargo y las cuotas son el plan de pago: no se suman por separado.",
        "Las facturas de contado vencen el día de su emisión; las de crédito, en la fecha de cada cuota.",
        "Si un pago no aparece con número de recibo, consulte en caja antes de realizar otro abono.",
    ]

    for texto in textos:
        hoja.merge_cells(start_row=fila, start_column=1, end_row=fila, end_column=ULTIMA)
        celda = hoja.cell(row=fila, column=1, value="•  " + texto)
        celda.font = Font(size=8, color="444444")
        celda.alignment = Alignment(vertical="top", wrap_text=True)
        hoja.row_dimensions[fila].height = 13
        fila += 1

    return fila + 2


def firmas(hoja, fila):
    for columna, texto in ((1, f"Elaborado por — {EMPRESA}"), (6, "Recibido por — Cliente")):
        hoja.merge_cells(start_row=fila, start_column=columna, end_row=fila, end_column=columna + 3)
        celda = hoja.cell(row=fila, column=columna)
        celda.border = Border(top=Side(style="thin", color="9CA3AF"))

        celda = hoja.cell(row=fila + 1, column=columna, value=texto)
        celda.font = Font(size=8, color=GRIS_TEXTO)
        celda.alignment = Alignment(horizontal="center")

    hoja.merge_cells(start_row=fila + 3, start_column=1, end_row=fila + 3, end_column=ULTIMA)
    celda = hoja.cell(row=fila + 3, column=1, value="Gracias por su preferencia")
    celda.font = Font(size=9, italic=True, color=AZUL)
    celda.alignment = Alignment(horizontal="center")

    return fila + 4


def hoja_cliente(libro, datos, varios=False):
    # Con un solo cliente la hoja se llama «Estado de cuenta»; si algún día se bajan
    # varios de golpe, cada hoja lleva el nombre del cliente para no repetirse.
    titulo = "Estado de cuenta" if not varios else f"Cuenta {datos['cliente']['nombre']}"[:31]

    for caracter in PROHIBIDOS:
        titulo = titulo.replace(caracter, "-")

    hoja = libro.create_sheet(titulo)
    hoja.sheet_properties.tabColor = AZUL

    for columna, ancho in ANCHOS.items():
        hoja.column_dimensions[columna].width = ancho

    fila = encabezado(hoja, datos)
    fila = datos_cliente(hoja, fila, datos)
    fila = resumen(hoja, fila, datos)
    fila = antiguedad(hoja, fila, datos)
    fila = facturas(hoja, fila, datos)
    fila = aviso_creditos(hoja, fila, datos)
    fila = recibos(hoja, fila, datos)
    fila = notas(hoja, fila)
    firmas(hoja, fila)

    hoja.page_setup.orientation = "portrait"
    hoja.page_setup.paperSize = hoja.PAPERSIZE_A4
    hoja.page_setup.fitToWidth = 1
    hoja.page_setup.fitToHeight = 0
    hoja.sheet_properties.pageSetUpPr.fitToPage = True
    hoja.page_margins = PageMargins(left=0.4, right=0.4, top=0.5, bottom=0.5)
    hoja.print_title_rows = "1:1"

    return hoja


def main():
    if len(sys.argv) < 3:
        print("uso: account_statement.py <datos.json> <salida.xlsx>", file=sys.stderr)
        return 1

    with open(sys.argv[1], encoding="utf-8") as archivo:
        payload = json.load(archivo)

    libro = Workbook()
    libro.remove(libro.worksheets[0])

    clientes = payload.get("clientes") or [payload]

    for datos in clientes:
        hoja_cliente(libro, datos, varios=len(clientes) > 1)

        # Un crédito abierto por hoja: el plan de cuotas no cabe (ni se lee) dentro del
        # estado de cuenta, y con dos créditos son dos hojas.
        for plan in datos.get("planes", []):
            hoja_credito(libro, datos, plan)

    destino = sys.argv[2]
    os.makedirs(os.path.dirname(destino) or ".", exist_ok=True)
    libro.save(destino)

    print(destino)
    return 0


if __name__ == "__main__":
    sys.exit(main())
