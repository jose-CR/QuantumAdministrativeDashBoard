<?php

namespace App\Models;

use App\Support\ElSalvadorCatalogo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Tags\HasTags;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory, HasTags;

    /**
     * Tipos de documento que entienden el formulario, la importación y la base.
     */
    public const DOCUMENT_TYPES = ['DUI', 'NIT', 'PASSPORT', 'RES CARNET', 'OTRO'];

    /*
     * El cliente se valida en el modelo, no solo en el formulario: la importación
     * escribe por otro camino y antes podía meter un tipo de documento o una
     * geografía que el formulario no acepta.
     */
    protected static function booted(): void
    {
        static::saving(function (Customer $customer): void {
            $customer->assertIdentityIsValid();
        });

        static::deleting(function (Customer $customer): bool {
            // La base borra en cascada créditos, cuotas, pagos y alertas: si el
            // cliente tiene historial, se frena.
            if (! $customer->canBeDeleted()) {
                return false;
            }

            // Los adjuntos son polimórficos y no tienen FK: la base no los borra.
            $customer->attachments()->get()->each->delete();

            return true;
        });
    }

    protected function assertIdentityIsValid(): void
    {
        if (! in_array($this->document_type, self::DOCUMENT_TYPES, true)) {
            throw ValidationException::withMessages([
                'document_type' => __('ui.validation.document_type_invalid'),
            ]);
        }

        $duplicado = static::query()
            ->where('document_type', $this->document_type)
            ->where('document_number', $this->document_number)
            ->when($this->exists, fn ($consulta) => $consulta->whereKeyNot($this->getKey()))
            ->exists();

        if ($duplicado) {
            throw ValidationException::withMessages([
                'document_number' => __('ui.validation.document_taken'),
            ]);
        }

        $this->assertGeographyIsValid();
    }

    protected function assertGeographyIsValid(): void
    {
        if (! array_key_exists((string) $this->department, ElSalvadorCatalogo::departments())) {
            throw ValidationException::withMessages([
                'department' => 'Departamento desconocido.',
            ]);
        }

        if (! array_key_exists((string) $this->municipality, ElSalvadorCatalogo::municipalities((string) $this->department))) {
            throw ValidationException::withMessages([
                'municipality' => __('ui.validation.municipality_not_in_department'),
            ]);
        }

        if (! array_key_exists((string) $this->district, ElSalvadorCatalogo::districts((string) $this->municipality))) {
            throw ValidationException::withMessages([
                'district' => __('ui.validation.district_not_in_municipality'),
            ]);
        }
    }

    /**
     * Qué se llevaría por delante borrar este cliente.
     *
     * @return array{credits: int, credits_active: int, invoices: int, attachments: int}
     */
    public function deletionSummary(): array
    {
        return [
            'credits' => $this->credits()->count(),
            'credits_active' => $this->credits()->where('status', 'active')->count(),
            'invoices' => $this->inflows()->count(),
            'attachments' => $this->attachments()->count(),
        ];
    }

    /**
     * Un cliente con historial no se borra: se pierde la plata.
     */
    public function canBeDeleted(): bool
    {
        $resumen = $this->deletionSummary();

        return $resumen['credits'] === 0 && $resumen['invoices'] === 0;
    }

    /**
     * Créditos que impiden el borrado, para poder buscarlos.
     *
     * @return \Illuminate\Support\Collection<int, array{id: int, status: string}>
     */
    public function creditsBlockingDeletion(): \Illuminate\Support\Collection
    {
        return $this->credits()
            ->orderBy('id')
            ->get(['id', 'status'])
            ->map(fn (Credit $credit): array => [
                'id' => $credit->id,
                'status' => (string) $credit->status,
            ]);
    }


        /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'document_type',
        'document_number',
        'full_name',
        'email',
        'phone_primary',
        'phone_secondary',
        'nrc',
        'economic_activity',
        'department',
        'municipality',
        'district',
        'address',
    ];

    /**
     *
     *
     *
     */

    public function references()
    {
        return $this->hasMany(ClientReference::class);
    }

    public function credits()
    {
        return $this->hasMany(Credit::class);
    }

    public function inflows()
    {
        return $this->hasMany(Inflow::class);
    }

    public function activeCredit()
    {
        return $this->hasOne(Credit::class)
            ->where('status', 'active');
    }

    public function latestCredit()
    {
        return $this->hasOne(Credit::class)
            ->latestOfMany('created_at');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public static function getTagClassName(): string
    {
        return Tag::class;
    }
}
