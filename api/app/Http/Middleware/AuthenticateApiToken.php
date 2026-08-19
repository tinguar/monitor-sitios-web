<?php

namespace App\Http\Middleware;

use App\Models\MonitorToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();
        if (! is_string($plain) || $plain === '') {
            return response()->json(['error' => 'Inicia sesión para continuar'], 401);
        }

        $token = MonitorToken::query()
            ->where('token_hash', hash('sha256', $plain))
            ->first();

        $expiresAt = $token?->expires_at;
        if (is_string($expiresAt) && $expiresAt !== '') {
            $expiresAt = \Carbon\Carbon::parse($expiresAt);
        }

        if (! $token || ! $expiresAt || $expiresAt->isPast()) {
            $token?->delete();

            return response()->json(['error' => 'Sesión expirada. Vuelve a iniciar sesión.'], 401);
        }

        $user = $token->user;
        if (! $user) {
            $token->delete();

            return response()->json(['error' => 'Inicia sesión para continuar'], 401);
        }

        try {
            $token->forceFill(['last_used_at' => now()])->save();
        } catch (\Throwable) {
            // La sesión sigue válida aunque no se pueda guardar last_used_at.
        }

        $request->setUserResolver(static fn () => $user);

        return $next($request);
    }
}
