"""Boletas de pago: llena la plantilla del taller, una boleta por empleado.

Entrada: un JSON con
    {"template": "...xlsx", "rows": [{NOMBRE, FECHA, SALARIO, ISSS, AFP,
     "TOTAL DESCUENTOS", "SUELDO NETO", BONO, TOTAL, "TOTAL RECIBIDO"}, ...]}

Salida: un xlsx con una hoja "Boletas" y un bloque por empleado, listo para
imprimir una vez y firmar. Se reemplaza lo que hacía la Hoja2 escrita a mano.
"""

import json
import sys
from copy import copy

from openpyxl import load_workbook

# La plantilla repite el bloque cada 52 filas: 50 de boleta (una copia para el
# empleado y otra para firmar) y 2 de separación.
BLOCK_START = 1
BLOCK_END = 50
BLOCK_STEP = 52


def copy_row_widths(source, target):
    for key, dimension in source.column_dimensions.items():
        target.column_dimensions[key].width = dimension.width


def fill_placeholders(sheet, first_row, last_row, data):
    replaced = 0

    for row in range(first_row, last_row + 1):
        for column in range(1, sheet.max_column + 1):
            cell = sheet.cell(row, column)

            if not isinstance(cell.value, str) or "{{" not in cell.value:
                continue

            key = cell.value.strip().removeprefix("{{").removesuffix("}}").strip()

            if key in data and data[key] is not None:
                cell.value = data[key]
                replaced += 1

    return replaced


def main(payload_path, output_path):
    with open(payload_path, encoding="utf-8") as handle:
        payload = json.load(handle)

    book = load_workbook(payload["template"])
    template = book["PlantillaBoletaDePago"]

    sheets = book.create_sheet("Boletas")
    copy_row_widths(template, sheets)

    for index, row in enumerate(payload["rows"]):
        start = BLOCK_START + index * BLOCK_STEP

        for offset in range(BLOCK_START, BLOCK_END + 1):
            source = template
            target = sheets

            for column in range(1, template.max_column + 1):
                origin = source.cell(offset, column)
                dest = target.cell(start + offset - 1, column)

                dest.value = origin.value

                if origin.has_style:
                    dest._style = copy(origin._style)

            height = template.row_dimensions[offset].height

            if height:
                target.row_dimensions[start + offset - 1].height = height

        replaced = fill_placeholders(sheets, start, start + BLOCK_END - 1, row)

        if replaced == 0:
            print(f"Aviso: la boleta {index + 1} no reemplazó nada.", file=sys.stderr)

    for name in list(book.sheetnames):
        if name != "Boletas":
            del book[name]

    book.save(output_path)
    print(f"Boletas generadas: {len(payload['rows'])}")


if __name__ == "__main__":
    main(sys.argv[1], sys.argv[2])
