<?php

namespace App\Exceptions;

use InvalidArgumentException;

/**
 * Algo del negocio no se puede hacer (pagar más que el saldo, editar un crédito
 * cerrado, mover stock que no hay).
 *
 * Lleva dos cosas para que el usuario se entere *qué* pasó y *dónde*:
 *  - `getMessage()`: el motivo, ya traducido;
 *  - `campo`: el campo del formulario que lo causó, si hay uno. Con campo, la pantalla
 *    lo marca en rojo y el mensaje sale debajo del input; sin campo, va como aviso.
 *
 * Antes cada servicio lanzaba `InvalidArgumentException` con un texto suelto (o en
 * español escrito en el código) y la pantalla solo podía mostrar «error».
 */
class BusinessRuleException extends InvalidArgumentException
{
    public function __construct(string $motivo, public readonly ?string $campo = null)
    {
        parent::__construct($motivo);
    }

    /**
     * El error corresponde a un campo del formulario.
     */
    public static function field(string $campo, string $motivo): self
    {
        return new self($motivo, $campo);
    }

    /**
     * El error es del documento completo, no de un campo.
     */
    public static function document(string $motivo): self
    {
        return new self($motivo);
    }
}
