<?php

namespace App\Support\Apps;

use Filament\Support\Icons\Heroicon;

/**
 * Catálogo de "apps" del panel (estilo Odoo).
 *
 * Cada app es un grupo de navegación registrado en App\Providers\Filament\AdminPanelProvider.
 * Aquí solo vive la presentación: icono, color y descripción de cada grupo.
 */
class AppCatalog
{
    /**
     * @var array<string, array{group: string, icon: Heroicon, color: string}>
     */
    public const APPS = [
        'clients' => [
            'group' => 'models.others.clients',
            'icon' => Heroicon::OutlinedUsers,
            'color' => '#0d9488',
        ],
        'cash' => [
            'group' => 'models.others.cash',
            'icon' => Heroicon::OutlinedBanknotes,
            'color' => '#16a34a',
        ],
        'inventory' => [
            'group' => 'models.others.inventory',
            'icon' => Heroicon::OutlinedCube,
            'color' => '#7c3aed',
            'primary' => 'inventory/articles',
        ],
        'credits' => [
            'group' => 'models.others.credits',
            'icon' => Heroicon::OutlinedCreditCard,
            'color' => '#ea580c',
            'primary' => 'creditos/loans',
        ],
        'administration' => [
            'group' => 'models.others.administration',
            'icon' => Heroicon::OutlinedBuildingStorefront,
            'color' => '#2563eb',
        ],
        'settings' => [
            'group' => 'models.others.settings',
            'icon' => Heroicon::OutlinedCog6Tooth,
            'color' => '#64748b',
            'primary' => 'tags',
        ],
    ];

    /**
     * Devuelve la presentación de una app a partir del label (ya traducido) de su grupo.
     *
     * @return array{key: string, label: string, description: string, icon: Heroicon, color: string}|null
     */
    public static function forGroupLabel(string $label): ?array
    {
        foreach (self::APPS as $key => $app) {
            if (__($app['group']) !== $label) {
                continue;
            }

            return [
                'key' => $key,
                'label' => $label,
                'description' => __('models.others.descriptions.' . $key),
                'icon' => $app['icon'],
                'color' => $app['color'],
                'primary' => $app['primary'] ?? null,
            ];
        }

        return null;
    }

    /**
     * Orden en el que se muestran las apps en el lanzador.
     *
     * @return array<int, string>
     */
    public static function order(): array
    {
        return array_keys(self::APPS);
    }
}
