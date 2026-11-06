<?php

namespace App\Http\Middleware;

use App\Support\Modules\ModuleGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    /**
     * Corta el paso a un módulo cerrado antes de que llegue a la pantalla.
     *
     * Va en el stack autenticado del panel, después de Authenticate, para que
     * $request->user() ya exista y se pueda decidir si es administrador.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();
        $module = ModuleGate::resolve($path);

        if ($module === null || ! ModuleGate::blocks($path, $request->user())) {
            return $next($request);
        }

        return $this->blockedResponse($request, $module);
    }

    protected function blockedResponse(Request $request, string $module): Response
    {
        // Livewire y fetch no saben dibujar una página HTML: se les contesta JSON.
        // 423 = "Locked", correcto para "existe pero no está disponible ahora".
        if ($request->expectsJson() || $request->hasHeader('X-Livewire')) {
            return response()->json([
                'message' => __('modules.blocked.title'),
                'module' => $module,
            ], 423);
        }

        $prefix = config('modules.lang.module');

        return response()
            ->view('modules.disabled', [
                'module' => $module,
                'label' => __($prefix . '.' . $module . '.label'),
                'message' => __($prefix . '.' . $module . '.message'),
                'state' => ModuleGate::state($module),
                'canBypass' => ModuleGate::isBypass($request->user()),
            ], 403)
            // Sin caché: si el navegador guarda esta página, el usuario la sigue
            // viendo después de que abrimos el módulo.
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}