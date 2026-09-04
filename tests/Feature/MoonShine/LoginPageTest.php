<?php

namespace Tests\Feature\MoonShine;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_without_captcha(): void
    {
        Config::set('services.yacaptcha.enabled', false);

        $this->get('/login')
            ->assertOk()
            ->assertSee('Логин')
            ->assertSee('Пароль');
    }

    public function test_login_page_renders_with_captcha_enabled(): void
    {
        Config::set('services.yacaptcha.enabled', true);

        $this->get('/login')->assertOk();
    }

    public function test_root_redirects_guests_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_user_factory_produces_a_usable_moonshine_user(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('moonshine_users', ['id' => $user->id, 'email' => $user->email]);
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
