<?php

namespace Tests\Feature\Api\V1;

use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyApiKeyTest extends TestCase
{
    use RefreshDatabase;

    private const PROTECTED_ROUTE = '/api/v1/keys';

    public function test_rejects_request_without_any_key(): void
    {
        $this->getJson(self::PROTECTED_ROUTE)
            ->assertStatus(401)
            ->assertJson(['message' => 'Не указан ключ API']);
    }

    public function test_rejects_unknown_key(): void
    {
        $this->getJson(self::PROTECTED_ROUTE, ['X-API-KEY' => 'does-not-exist'])
            ->assertStatus(401)
            ->assertJson(['message' => 'Неверный или неактивный ключ API']);
    }

    public function test_rejects_inactive_key(): void
    {
        $apiKey = ApiKey::factory()->inactive()->create();

        $this->getJson(self::PROTECTED_ROUTE, ['X-API-KEY' => $apiKey->key])
            ->assertStatus(401)
            ->assertJson(['message' => 'Неверный или неактивный ключ API']);
    }

    public function test_rejects_expired_key(): void
    {
        $apiKey = ApiKey::factory()->active()->expiresAt(now()->subDay())->create();

        $this->getJson(self::PROTECTED_ROUTE, ['X-API-KEY' => $apiKey->key])
            ->assertStatus(401)
            ->assertJson(['message' => 'Срок действия ключа API истёк']);
    }

    public function test_unlimited_key_ignores_past_expiry(): void
    {
        $apiKey = ApiKey::factory()->active()->unlimited()->create();
        $apiKey->forceFill(['expires_at' => now()->subYear()])->saveQuietly();

        $this->getJson(self::PROTECTED_ROUTE, ['X-API-KEY' => $apiKey->fresh()->key])
            ->assertStatus(200);
    }

    public function test_active_key_without_expiry_passes(): void
    {
        $apiKey = ApiKey::factory()->active()->create(['is_unlimited' => false, 'expires_at' => null]);

        $this->getJson(self::PROTECTED_ROUTE, ['X-API-KEY' => $apiKey->key])
            ->assertStatus(200);
    }

    public function test_active_key_with_future_expiry_passes(): void
    {
        $apiKey = ApiKey::factory()->active()->expiresAt(now()->addMonth())->create();

        $this->getJson(self::PROTECTED_ROUTE, ['X-API-KEY' => $apiKey->key])
            ->assertStatus(200);
    }

    public function test_accepts_key_via_bearer_token_as_fallback(): void
    {
        $apiKey = ApiKey::factory()->active()->create(['is_unlimited' => false, 'expires_at' => null]);

        $this->withHeader('Authorization', 'Bearer '.$apiKey->key)
            ->getJson(self::PROTECTED_ROUTE)
            ->assertStatus(200);
    }
}
