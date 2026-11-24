<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Tipo de documento fiscal de una entrada o salida de caja.
 *
 * Es catálogo y no lista fija: el taller agrega el tipo que necesite desde
 * Configuración → Tipos de documento sin tocar código ni migrar nada.
 *
 * El `code` es lo que se guarda en `inflows.document_type` / `outflows.document_type`;
 * el `name` es lo que se ve. `scope` dice dónde aplica.
 */
class DocumentType extends Model
{
    public const SCOPE_INFLOW = 'inflow';

    public const SCOPE_OUTFLOW = 'outflow';

    public const SCOPE_BOTH = 'both';

    public const SCOPES = [
        self::SCOPE_INFLOW,
        self::SCOPE_OUTFLOW,
        self::SCOPE_BOTH,
    ];

    protected $fillable = [
        'code',
        'name',
        'scope',
        'active',
        'sort',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort' => 'integer',
    ];

    protected static function booted(): void
    {
        // El código se guarda en la caja: en minúsculas y sin espacios, o cada alta
        // crea un tipo distinto para el mismo documento.
        static::saving(function (DocumentType $tipo): void {
            $tipo->code = mb_strtolower(trim((string) $tipo->code));
        });

        // El catálogo se cachea por petición: después de guardar hay que soltarlo o el
        // tipo nuevo no aparece en el formulario de Caja hasta la próxima carga.
        static::saved(fn () => \App\Support\DocumentTypes::flush());
        static::deleted(fn () => \App\Support\DocumentTypes::flush());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Los que puede llevar una entrada de caja.
     */
    public function scopeForInflow(Builder $query): Builder
    {
        return $query->active()->whereIn('scope', [self::SCOPE_INFLOW, self::SCOPE_BOTH]);
    }

    /**
     * Los que puede llevar una salida de caja.
     */
    public function scopeForOutflow(Builder $query): Builder
    {
        return $query->active()->whereIn('scope', [self::SCOPE_OUTFLOW, self::SCOPE_BOTH]);
    }

    /**
     * Cuántos documentos de caja lo usan (para no borrar un tipo en uso).
     */
    public function usageCount(): int
    {
        return (int) \App\Models\Inflow::query()->where('document_type', $this->code)->count()
            + (int) \App\Models\Outflow::query()->where('document_type', $this->code)->count();
    }
}
