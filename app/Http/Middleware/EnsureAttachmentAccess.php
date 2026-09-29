<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAttachmentAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            abort(403);
        }

        if (! $this->ipInAllowedRange($request->ip())) {
            abort(403, 'Acceso solo permitido desde la red del taller.');
        }

        return $next($request);
    }

    protected function ipInAllowedRange(?string $ip): bool
    {
        if (! $ip) {
            return false;
        }

        if (in_array($ip, ['127.0.0.1', '::1'], true)) {
            return true;
        }

        $cidr = config('attachments.allowed_cidr');

        if (! $cidr) {
            return false;
        }

        [$subnet, $bits] = explode('/', $cidr);

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $mask = -1 << (32 - (int) $bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}