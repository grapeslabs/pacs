<?php

namespace Tests\Unit\Services\Otp;

use App\Services\Otp\ClientId;
use App\Services\Otp\FalsyDriver;
use App\Services\Otp\GrapesFlashcalls;
use App\Services\Otp\OtpManager;
use App\Services\Otp\Password;
use App\Services\Otp\TruthyDriver;
use InvalidArgumentException;
use Tests\TestCase;

class OtpManagerTest extends TestCase
{
    private function manager(string $default = 'truthy'): OtpManager
    {
        return new OtpManager([
            'default' => $default,
            'connections' => [
                'truthy' => ['driver' => 'truthy'],
                'falsy' => ['driver' => 'falsy'],
                'flashcalls' => [
                    'driver' => 'grapesFlashcalls',
                    'url' => 'https://flash.test/',
                    'token' => 'tok',
                ],
                'broken' => ['driver' => 'carrierPigeon'],
            ],
        ]);
    }

    public function test_driver_without_a_name_resolves_the_default(): void
    {
        $this->assertInstanceOf(TruthyDriver::class, $this->manager('truthy')->driver());
        $this->assertInstanceOf(FalsyDriver::class, $this->manager('falsy')->driver());
    }

    public function test_driver_by_name_overrides_the_default(): void
    {
        $this->assertInstanceOf(FalsyDriver::class, $this->manager('truthy')->driver('falsy'));
    }

    public function test_resolved_drivers_are_memoized(): void
    {
        $manager = $this->manager();

        $this->assertSame($manager->driver('falsy'), $manager->driver('falsy'));
    }

    public function test_resolve_bypasses_the_memoization_cache(): void
    {
        $manager = $this->manager();

        $this->assertNotSame($manager->resolve('falsy'), $manager->resolve('falsy'));
    }

    public function test_flashcalls_driver_is_built_from_connection_config(): void
    {
        $driver = $this->manager()->driver('flashcalls');

        $this->assertInstanceOf(GrapesFlashcalls::class, $driver);
    }

    public function test_unsupported_driver_name_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Driver [carrierPigeon] is not supported.');

        $this->manager()->driver('broken');
    }

    public function test_unknown_connection_raises_a_php_warning_before_the_intended_exception(): void
    {
        $manager = $this->manager();

        $warnings = [];
        set_error_handler(function (int $no, string $message) use (&$warnings): bool {
            $warnings[] = $message;

            return true;
        }, E_WARNING);

        try {
            $manager->resolve('nope');
            $this->fail('ожидался InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('OTP connection [nope] is not defined.', $e->getMessage());
        } finally {
            restore_error_handler();
        }

        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString('Undefined array key', $warnings[0]);
    }

    public function test_get_default_driver_reads_config(): void
    {
        $this->assertSame('flashcalls', $this->manager('flashcalls')->getDefaultDriver());
    }

    public function test_truthy_driver_accepts_any_password(): void
    {
        $driver = new TruthyDriver();

        $this->assertTrue($driver->check(new ClientId('79990000000'), new Password('000000')));
        $this->assertTrue($driver->check(new ClientId('79990000000'), new Password('чепуха')));
    }

    public function test_falsy_driver_rejects_any_password(): void
    {
        $driver = new FalsyDriver();

        $this->assertFalse($driver->check(new ClientId('79990000000'), new Password('000000')));
    }

    public function test_stub_drivers_send_nothing(): void
    {
        (new TruthyDriver())->send(new ClientId('79990000000'));
        (new FalsyDriver())->send(new ClientId('79990000000'));

        $this->addToAssertionCount(1);
    }

    public function test_application_container_wires_the_manager_from_config_otp(): void
    {
        config()->set('otp.default', 'falsy');
        $this->app->forgetInstance(OtpManager::class);

        $this->assertInstanceOf(FalsyDriver::class, $this->app->make(OtpManager::class)->driver());
    }
}
