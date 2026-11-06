<?php

namespace App\Filament\Admin\pages;

use App\Support\Apps\AppCatalog;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use BackedEnum;

/**
 * Lanzador de aplicaciones (equivalente a la pantalla de inicio de Odoo).
 *
 * Se alimenta de la navegación real del panel, así que respeta los permisos:
 * si un usuario no puede ver un recurso, su app no aparece en su lanzador.
 */
class Apps extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $slug = 'apps';

    protected static ?int $navigationSort = -10;

    protected string $view = 'filament.admin.pages.apps';

    public static function getNavigationLabel(): string
    {
        return __('models.others.apps');
    }

    public function getTitle(): string
    {
        return __('models.others.apps');
    }

    public function getHeading(): string
    {
        return __('models.others.apps');
    }

    public function getSubheading(): ?string
    {
        return __('models.others.apps_subheading');
    }

    /**
     * Arma las tarjetas del lanzador a partir de los grupos de navegación del panel.
     *
     * @return array<int, array{key: string, label: string, description: string, icon: mixed, color: string, url: ?string, items: array<int, array{label: string, url: string, icon: mixed}>}>
     */
    public function getApps(): array
    {
        $apps = [];
        $general = null;

        foreach (filament()->getNavigation() as $group) {
            if (! $group instanceof NavigationGroup) {
                continue;
            }

            $items = $this->itemsFrom($group);

            if ($items === []) {
                continue;
            }

            $label = (string) ($group->getLabel() ?? '');

            // Los recursos sin grupo no forman una app propia: van a "General".
            if (trim($label) === '') {
                $general ??= $this->emptyGeneralApp();

                foreach ($items as $item) {
                    $general['items'][] = $item;
                }

                $general['url'] ??= $items[0]['url'];

                continue;
            }

            $meta = AppCatalog::forGroupLabel($label);

            $apps[] = [
                'key' => $meta['key'] ?? 'group-' . md5($label),
                'label' => $label,
                'description' => $meta['description'] ?? '',
                'icon' => $meta['icon'] ?? $group->getIcon() ?? Heroicon::OutlinedRectangleStack,
                'color' => $meta['color'] ?? '#64748b',
                'url' => $this->primaryUrl($items, $meta),
                'items' => $items,
            ];
        }

        if ($general !== null && $general['items'] !== []) {
            array_unshift($apps, $general);
        }

        // Respeta el orden declarado en el catálogo; lo desconocido va al final.
        $order = array_flip(AppCatalog::order());

        usort($apps, function (array $a, array $b) use ($order) {
            $posA = $order[$a['key']] ?? PHP_INT_MAX;
            $posB = $order[$b['key']] ?? PHP_INT_MAX;

            return $posA <=> $posB;
        });

        return $apps;
    }

    /**
     * @return array{key: string, label: string, description: string, icon: mixed, color: string, url: ?string, items: array<int, array{label: string, url: string, icon: mixed}>}
     */
    protected function emptyGeneralApp(): array
    {
        return [
            'key' => 'general',
            'label' => __('models.others.general'),
            'description' => __('models.others.descriptions.general'),
            'icon' => Heroicon::OutlinedHome,
            'color' => '#475569',
            'url' => null,
            'items' => [],
        ];
    }

    /**
     * Ítems visibles de un grupo, sin la propia pantalla de aplicaciones.
     *
     * @return array<int, array{label: string, url: string, icon: mixed}>
     */
    protected function itemsFrom(NavigationGroup $group): array
    {
        $selfUrl = static::getUrl();
        $items = [];

        foreach ($group->getItems() as $item) {
            if (! $item instanceof NavigationItem) {
                continue;
            }

            $url = (string) $item->getUrl();

            if (blank($url) || $url === $selfUrl) {
                continue;
            }

            $items[] = [
                'label' => (string) $item->getLabel(),
                'url' => $url,
                'icon' => $item->getIcon(),
            ];
        }

        return $items;
    }

    /**
     * URL de entrada de la app: la declarada como principal en el catálogo, o la primera.
     *
     * @param  array<int, array{label: string, url: string, icon: mixed}>  $items
     * @param  array{key: string, label: string, description: string, icon: mixed, color: string, primary?: string}|null  $meta
     */
    protected function primaryUrl(array $items, ?array $meta): ?string
    {
        $primary = $meta['primary'] ?? null;

        if (filled($primary)) {
            foreach ($items as $item) {
                $path = rtrim((string) parse_url($item['url'], PHP_URL_PATH), '/');

                if (str_ends_with($path, '/' . trim($primary, '/'))) {
                    return $item['url'];
                }
            }
        }

        return $items[0]['url'] ?? null;
    }
}
