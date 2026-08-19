<?php

namespace Tests;

use App\Models\MonitorToken;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function actingAsMonitor(?User $user = null): User
    {
        $user ??= User::factory()->create();
        $plain = bin2hex(random_bytes(32));
        MonitorToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addHours(8),
        ]);

        $this->withToken($plain);

        return $user;
    }
}
