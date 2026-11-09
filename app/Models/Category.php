<?php

namespace App\Models;

use App\Models\Concerns\HasCreditBlockedDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasCreditBlockedDeletion, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * `id` no va aquí: permitirlo dejaba crear registros con un id elegido a
     * mano y desfasaba la secuencia de Postgres (el error de "id duplicado").
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    protected static function booted(): void
    {
        /*
         * Borrar una categoría arrastra sus artículos y las unidades de esos
         * artículos: la llave foránea es RESTRICT, así que sin esto habría que
         * borrar los hijos a mano (imposible con miles de artículos).
         *
         * Se cancela si alguna unidad está usada en un crédito: esos ítems son
         * polimórficos y no tienen FK, así que el crédito quedaría apuntando a
         * una unidad inexistente.
         */
        static::deleting(function (Category $category): bool {
            if (! $category->canBeDeletedWithChildren()) {
                return false;
            }

            $articleIds = $category->articles()->pluck('id');

            DB::transaction(function () use ($articleIds): void {
                ArticleUnit::query()->whereIn('article_id', $articleIds)->delete();
                Article::query()->whereIn('id', $articleIds)->delete();
            });

            return true;
        });
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Qué se lleva por delante borrar esta categoría.
     *
     * @return array{articles: int, units: int, units_in_credits: int}
     */
    public function deletionSummary(): array
    {
        $articleIds = $this->articles()->pluck('id');

        if ($articleIds->isEmpty()) {
            return ['articles' => 0, 'units' => 0, 'units_in_credits' => 0];
        }

        $units = ArticleUnit::query()->whereIn('article_id', $articleIds);

        return [
            'articles' => $articleIds->count(),
            'units' => (clone $units)->count(),
            'units_in_credits' => (clone $units)
                ->whereIn('id', CreditItem::query()
                    ->select('item_id')
                    ->where('item_type', ArticleUnit::class))
                ->count(),
        ];
    }

    public function canBeDeletedWithChildren(): bool
    {
        return $this->deletionSummary()['units_in_credits'] === 0;
    }

    /**
     * Créditos que impiden el borrado, para poder buscarlos: id y cliente.
     *
     * @return \Illuminate\Support\Collection<int, array{id: int, customer: string}>
     */
    public function creditsBlockingDeletion(): \Illuminate\Support\Collection
    {
        $unitIds = ArticleUnit::query()
            ->whereIn('article_id', $this->articles()->select('id'))
            ->select('id');

        return Credit::query()
            ->whereIn('id', CreditItem::query()
                ->select('credit_id')
                ->where('item_type', ArticleUnit::class)
                ->whereIn('item_id', $unitIds))
            ->with('client:id,full_name')
            ->orderBy('id')
            ->get()
            ->map(fn (Credit $credit): array => [
                'id' => $credit->id,
                'customer' => (string) ($credit->client?->full_name ?? ''),
            ]);
    }
}
