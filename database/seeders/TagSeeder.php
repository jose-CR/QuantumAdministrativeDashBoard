<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

/**
 * Etiquetas base para segmentar clientes.
 *
 * Son pocas a propósito: una etiqueta que nadie usa solo ensucia el filtro.
 * Se pueden agregar más desde Configuración → Etiquetas.
 */
class TagSeeder extends Seeder
{
    public function run(): void
    {
        $etiquetas = [
            // Comercial
            'mayorista',
            'taller',
            'flota',
            'vip',
            'frecuente',

            // Operación
            'garantia',
            'solo-contado',
            'envio',
        ];

        foreach ($etiquetas as $nombre) {
            Tag::findOrCreate($nombre, 'customer');
        }
    }
}
