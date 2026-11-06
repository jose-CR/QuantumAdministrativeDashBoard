<?php

namespace App\Support\Modules;

use Illuminate\Contracts\Auth\Authenticatable;

class ModuleGate
{
    /** Estados válidos, de más abierto a más cerrado. */
    public const ENABLED = 'enabled';

    public const PREVIEW = 'preview';

    public const OFF = 'off';

    /** Estado leído del JSON, memorizado una vez por request. */
    protected static ?array $overrides = null;

    /**
     * Todos los módulos declarados en config/modules.php.
     *
     * @return array<string, array{state: string, paths: array<int, string>}>
     */
    public static function modules(): array
    {
        return config('modules.modules', []);
    }

    public static function exists(string $module): bool
    {
        return array_key_exists($module, static::modules());
    }

    /**
     * @return array<int, string>
     */
    public static function states(): array
    {
        return [self::ENABLED, self::PREVIEW, self::OFF];
    }

    /**
     * Estado efectivo: lo que dice el JSON si existe, si no lo que dice el config.
     * Un valor desconocido cae en "preview": nadie queda afuera por un error de tipeo.
     */
    public static function state(string $module): string
    {
        $config = static::modules()[$module]['state'] ?? config('modules.default_state', self::ENABLED);

        $state = static::overrides()[$module] ?? $config;

        if (! in_array($state, static::states(), true)) {
            return (string) config('modules.invalid_state_fallback', self::PREVIEW);
        }

        return $state;
    }

    public static function isEnabled(string $module): bool
    {
        return static::state($module) === self::ENABLED;
    }

    /**
     * Módulo dueño de una ruta, o null si no la maneja ninguno.
     * Acepta la ruta con o sin el prefijo del panel (/admin).
     */
    public static function resolve(?string $path): ?string
    {
        $path = static::normalize($path);

        if ($path === '') {
            return null;
        }

        $matches = [];

        foreach (static::modules() as $module => $definition) {
            foreach ($definition['paths'] ?? [] as $slug) {
                $slug = trim($slug, '/');

                if ($path === $slug || str_starts_with($path, $slug . '/')) {
                    $matches[] = ['module' => $module, 'length' => strlen($slug)];
                }
            }
        }

        if ($matches === []) {
            return null;
        }

        // El slug más específico gana: translation-manager/quick-translate antes que translation-manager.
        usort($matches, static fn (array $a, array $b): int => $b['length'] <=> $a['length']);

        return $matches[0]['module'];
    }

    /**
     * ¿Hay que mostrarle la página de aviso a este usuario en esta ruta?
     */
    public static function blocks(?string $path, ?Authenticatable $user = null): bool
    {
        $module = static::resolve($path);

        if ($module === null) {
            return false;
        }

        return match (static::state($module)) {
            self::ENABLED => false,
            self::OFF => true,
            default => ! static::isBypass($user),
        };
    }

    /**
     * Quien puede pasar en "preview": el rol configurado en modules.bypass_role.
     */
    public static function isBypass(?Authenticatable $user = null): bool
    {
        $role = config('modules.bypass_role');

        if ($user === null || blank($role) || ! method_exists($user, 'hasRole')) {
            return false;
        }

        return $user->hasRole($role);
    }

    /**
     * Estados escritos a mano en storage, sin pasar por el config.
     *
     * @return array<string, string>
     */
    public static function overrides(): array
    {
        if (static::$overrides !== null) {
            return static::$overrides;
        }

        $file = static::stateFile();

        if (! is_file($file)) {
            return static::$overrides = [];
        }

        $data = json_decode((string) file_get_contents($file), true);

        return static::$overrides = is_array($data) ? array_filter($data, 'is_string') : [];
    }

    /**
     * Cambia el estado de un módulo sin deploy. Escritura atómica: nunca deja un JSON a medias.
     */
    public static function setState(string $module, string $state): void
    {
        $overrides = static::overrides();
        $overrides[$module] = $state;

        $file = static::stateFile();
        $directory = dirname($file);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $temp = $file . '.' . getmypid() . '.tmp';

        file_put_contents($temp, json_encode($overrides, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        rename($temp, $file);

        static::flush();
    }

    /** Olvida lo memorizado (útil después de escribir el JSON o en los tests). */
    public static function flush(): void
    {
        static::$overrides = null;
    }

    public static function stateFile(): string
    {
        return (string) config('modules.state_file', storage_path('app/module-states.json'));
    }

    /**
     * Quita barras y el prefijo del panel: "admin/creditos/loans" → "creditos/loans".
     */
    protected static function normalize(?string $path): string
    {
        $path = trim((string) $path, '/');
        $panel = trim((string) config('modules.panel_path', 'admin'), '/');

        if ($panel === '') {
            return $path;
        }

        if ($path === $panel) {
            return '';
        }

        if (str_starts_with($path, $panel . '/')) {
            return substr($path, strlen($panel) + 1);
        }

        return $path;
    }
}