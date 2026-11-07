<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ArticleUnit extends Model
{
    /** @use HasFactory<\Database\Factories\ArticleUnitFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'article_id',
        'color',
        'cash_price', // precio al contado
        'vin', // número de serie del vehículo; para otros serializados, su serie
        'engine_number', // Motor
        'plate', // Placa
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cash_price' => 'decimal:2',
        ];
    }

    /**
     * El estado lo pone la base ('available'); declararlo aquí evita que un
     * modelo recién creado lo muestre vacío hasta recargarlo.
     */
    protected $attributes = [
        'status' => 'available',
    ];

    protected static function booted(): void
    {
        /*
         * Una unidad solo existe para artículos serializados y siempre con su
         * serie: así lo que entra por el panel, por importación o por código no
         * puede quedar sin identificar. Se lanza como ValidationException para
         * que Filament la muestre en el campo y no como un error 500.
         */
        static::saving(function (ArticleUnit $unit) {
            $article = $unit->article_id ? Article::find($unit->article_id) : null;

            if ($article === null) {
                throw ValidationException::withMessages([
                    'article_id' => 'Selecciona el artículo al que pertenece la unidad.',
                ]);
            }

            if (! $article->tracksSerials()) {
                throw ValidationException::withMessages([
                    'article_id' => "«{$article->full_name}» se maneja por cantidad: usa el stock del artículo, no unidades.",
                ]);
            }

            if (blank($unit->vin)) {
                throw ValidationException::withMessages([
                    'vin' => 'El VIN o número de serie es obligatorio para artículos serializados.',
                ]);
            }
        });
    }

    public function getDisplayNameAttribute(): string
    {
        return collect([
            trim(($this->article?->full_name ?? '') . ' ' . ($this->vin ?? '')),
            $this->color,
            $this->plate,
        ])
            ->filter()
            ->implode(' • ');
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function creditItems()
    {
        return $this->morphMany(CreditItem::class, 'item');
    }
}
