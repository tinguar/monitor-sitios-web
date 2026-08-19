<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MonitorToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $password = (string) $request->input('password', '');

        if ($email === '' || $password === '') {
            return response()->json(['error' => 'Correo y contraseña son obligatorios'], 422);
        }

        $user = User::query()->where('email', $email)->first();
        if (! $user || ! Hash::check($password, $user->password)) {
            return response()->json(['error' => 'Correo o contraseña incorrectos'], 401);
        }

        MonitorToken::query()->where('user_id', $user->id)->delete();

        $plain = bin2hex(random_bytes(32));
        MonitorToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plain),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255) ?: null,
            'expires_at' => now()->addHours(8),
        ]);

        return response()->json([
            'token' => $plain,
            'expires_in_hours' => 8,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'name' => $user?->name,
            'email' => $user?->email,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $plain = $request->bearerToken();
        if (is_string($plain) && $plain !== '') {
            MonitorToken::query()
                ->where('token_hash', hash('sha256', $plain))
                ->delete();
        }

        return response()->json(['ok' => true]);
    }
}
