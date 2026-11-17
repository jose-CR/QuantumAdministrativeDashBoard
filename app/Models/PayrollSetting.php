<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Los parámetros de la planilla: una sola fila que el encargado ajusta desde el
 * panel cuando cambia la ley.
 */
class PayrollSetting extends Model
{
    protected $fillable = [
        'isss_rate',
        'isss_cap',
        'afp_rate',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'isss_rate' => 'decimal:2',
            'isss_cap' => 'decimal:2',
            'afp_rate' => 'decimal:2',
        ];
    }

    /**
     * Los parámetros vigentes; si todavía no hay fila, se crea con los valores de ley.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'isss_rate' => 3.00,
            'isss_cap' => 30.00,
            'afp_rate' => 7.25,
        ]);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
