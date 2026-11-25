<?php

namespace App\Support\Ui;

use App\Exceptions\BusinessRuleException;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Avisar al usuario qué se equivocó, en un solo lugar.
 *
 * Tres formas, según el caso:
 *  - `field()`: el error es de un campo → se marca el input en rojo con el mensaje debajo.
 *  - `stop()`: el error es del documento → aviso rojo con título corto y el motivo abajo,
 *    y la acción se detiene (no guarda nada a medias).
 *  - `fromRule()`: recibe la `BusinessRuleException` del servicio y elige una de las dos.
 *
 * El título dice *qué* se intentaba hacer; el cuerpo dice *por qué* no se pudo. Nunca
 * un «error» pelado: eso no le dice al usuario qué corregir.
 */
final class Alert
{
    /**
     * Marca el campo y corta: Filament muestra el mensaje debajo del input.
     */
    public static function field(string $campo, string $motivo): never
    {
        throw ValidationException::withMessages([$campo => $motivo]);
    }

    /**
     * Aviso rojo con el motivo y la acción detenida.
     */
    public static function stop(string $motivo, ?string $titulo = null): never
    {
        Notification::make()
            ->danger()
            ->title($titulo ?? __('ui.errors.title'))
            ->body($motivo)
            ->persistent()
            ->send();

        throw new Halt();
    }

    /**
     * Traduce la excepción del servicio a la pantalla que corresponda.
     */
    public static function fromRule(BusinessRuleException $error, ?string $titulo = null): never
    {
        if ($error->campo !== null) {
            self::field($error->campo, $error->getMessage());
        }

        self::stop($error->getMessage(), $titulo);
    }

    /**
     * Último recurso: algo inesperado. Se avisa sin filtrar detalles técnicos al usuario
     * y el error completo queda en el log.
     */
    public static function unexpected(Throwable $error, string $titulo): never
    {
        report($error);

        self::stop(__('ui.errors.unexpected'), $titulo);
    }

    /**
     * Para cerrar un modal propio (los que no son `Action` de Filament).
     */
    public static function halt(): never
    {
        throw new Halt();
    }
}
