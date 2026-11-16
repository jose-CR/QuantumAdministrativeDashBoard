<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Una persona que trabaja en el taller.
 *
 * No es lo mismo que `User`: el usuario es quien entra al panel y casi nunca todos
 * los empleados tienen uno. Cuando lo tienen, `user_id` los une.
 *
 * Las boletas de pago todavía no existen: van en su propia tabla cuando se
 * implementen (docs/futuro-pagos-empleados.md). Acá no se guarda salario.
 */
class Employee extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Puestos que entiende el taller. Las etiquetas visibles salen de `lang`, no de
     * acá: esto es el valor que se guarda.
     */
    public const JOB_TITLES = [
        'vendedor',
        'tecnico',
        'bodega',
        'administracion',
        'otro',
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    /** Tipos de documento: los mismos que acepta un cliente. */
    public const DOCUMENT_TYPES = Customer::DOCUMENT_TYPES;

    protected $fillable = [
        'code',
        'name',
        'document_type',
        'document_number',
        'phone',
        'email',
        'address',
        'job_title',
        'hire_date',
        'termination_date',
        'status',
        'user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'termination_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
