<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Una quincena que se paga (1-15 y 16-fin de mes).
 */
class PayrollPeriod extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_PAID = 'paid';

    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_PAID,
    ];

    protected $fillable = [
        'label',
        'start_date',
        'end_date',
        'payment_date',
        'type',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'payment_date' => 'date',
        ];
    }

    /**
     * Las fechas de una quincena. El último día lo pone el calendario (28, 29, 30 o
     * 31), nunca se escribe a mano.
     *
     * @return array{label: string, start_date: string, end_date: string, payment_date: string}
     */
    public static function datesFor(int $year, int $month, string $half): array
    {
        $primera = $half === 'first';

        $start = Carbon::create($year, $month, $primera ? 1 : 16);
        $end = $primera
            ? Carbon::create($year, $month, 15)
            : Carbon::create($year, $month, 1)->endOfMonth();

        return [
            'label' => sprintf('%d-%02d-Q%d', $year, $month, $primera ? 1 : 2),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'payment_date' => $end->toDateString(),
        ];
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class, 'period_id');
    }

    protected static function booted(): void
    {
        static::saving(function (PayrollPeriod $periodo): void {
            $duplicado = static::query()
                ->whereDate('start_date', $periodo->start_date)
                ->whereDate('end_date', $periodo->end_date)
                ->when($periodo->exists, fn ($consulta) => $consulta->whereKeyNot($periodo->getKey()))
                ->exists();

            if ($duplicado) {
                throw ValidationException::withMessages([
                    'start_date' => __('ui.payroll.period_exists'),
                ]);
            }
        });
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Total que se entrega en la quincena.
     */
    public function getTotalToPayAttribute(): float
    {
        return round((float) $this->payslips()->sum('total_to_receive'), 2);
    }

    /**
     * Fecha de pago como va en la boleta: «23 DE FEBRERO».
     */
    public function getPayslipDateAttribute(): string
    {
        return mb_strtoupper($this->payment_date->locale('es')->translatedFormat('d \\d\\e F'));
    }
}
