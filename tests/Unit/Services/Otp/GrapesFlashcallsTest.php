<?php

namespace Tests\Unit\Services\Otp;

use App\Services\Otp\ClientId;
use App\Services\Otp\GrapesFlashcalls;
use App\Services\Otp\Password;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GrapesFlashcallsTest extends TestCase
{
    private const PHONE = '79990000000';
    private const CACHE_KEY = 'otp_79990000000';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(self::CACHE_KEY);
    }

    private function driver(string $url = 'https://flash.test/'): GrapesFlashcalls
    {
        return new GrapesFlashcalls($url, 'secret-token');
    }

    private function clientId(): ClientId
    {
        return new ClientId(self::PHONE);
    }

    public function test_send_posts_a_flashcall_request_as_form(): void
    {
        Http::fake(['flash.test/*' => Http::response(['sms' => 'msg-1'], 200)]);

        $this->driver()->send($this->clientId());

        Http::assertSent(function (Request $r) {
            $this->assertSame('POST', $r->method());
            $this->assertSame('https://flash.test/api/v1/send', $r->url());
            $this->assertSame('Bearer secret-token', $r->header('Authorization')[0]);
            $this->assertStringContainsString('application/x-www-form-urlencoded', $r->header('Content-Type')[0]);
            $this->assertSame(self::PHONE, $r['number']);
            $this->assertSame('flashcall', $r['gateway']);

            return true;
        });
    }

    public function test_send_caches_the_returned_message_id(): void
    {
        Http::fake(['flash.test/*' => Http::response(['sms' => 'msg-42'], 200)]);

        $this->driver()->send($this->clientId());

        $this->assertSame('msg-42', Cache::get(self::CACHE_KEY));
    }

    public function test_send_drops_a_previous_message_id_before_asking_for_a_new_one(): void
    {
        Cache::put(self::CACHE_KEY, 'stale-id');
        Http::fake(['flash.test/*' => Http::response('rate limited', 429)]);

        $this->driver()->send($this->clientId());

        $this->assertNull(Cache::get(self::CACHE_KEY));
    }

    public function test_send_stores_nothing_when_the_gateway_fails(): void
    {
        Http::fake(['flash.test/*' => Http::response('boom', 500)]);

        $this->driver()->send($this->clientId());

        $this->assertNull(Cache::get(self::CACHE_KEY));
    }

    public function test_cached_message_id_never_expires(): void
    {
        Http::fake(['flash.test/*' => Http::response(['sms' => 'msg-1'], 200)]);

        $this->driver()->send($this->clientId());
        $this->travel(30)->days();

        $this->assertSame('msg-1', Cache::get(self::CACHE_KEY));
    }

    public function test_base_url_is_concatenated_without_a_separator(): void
    {
        Http::fake(['*' => Http::response(['sms' => 'x'], 200)]);

        $this->driver('https://flash.test')->send($this->clientId());

        Http::assertSent(fn (Request $r) => $r->url() === 'https://flash.testapi/v1/send');
    }

    public function test_check_without_a_pending_message_id_fails_offline(): void
    {
        Http::fake();

        $this->assertFalse($this->driver()->check($this->clientId(), new Password('1234')));

        Http::assertNothingSent();
    }

    public function test_check_sends_message_id_and_code(): void
    {
        Cache::put(self::CACHE_KEY, 'msg-7');
        Http::fake(['flash.test/*' => Http::response(['result' => 'ok'], 200)]);

        $this->driver()->check($this->clientId(), new Password('1234'));

        Http::assertSent(function (Request $r) {
            $this->assertSame('GET', $r->method());
            $this->assertStringStartsWith('https://flash.test/api/v1/flashcall/check_code', $r->url());
            $this->assertSame('msg-7', $r['message_id']);
            $this->assertSame('1234', $r['code']);

            return true;
        });
    }

    public function test_successful_check_clears_the_pending_message_id(): void
    {
        Cache::put(self::CACHE_KEY, 'msg-7');
        Http::fake(['flash.test/*' => Http::response(['result' => 'ok'], 200)]);

        $this->assertTrue($this->driver()->check($this->clientId(), new Password('1234')));
        $this->assertNull(Cache::get(self::CACHE_KEY));
    }

    public function test_failed_check_returns_false_and_keeps_the_message_id(): void
    {
        Cache::put(self::CACHE_KEY, 'msg-7');
        Http::fake(['flash.test/*' => Http::response('nope', 400)]);

        $this->assertFalse($this->driver()->check($this->clientId(), new Password('0000')));
        $this->assertSame('msg-7', Cache::get(self::CACHE_KEY));
    }

    public function test_check_accepts_any_code_as_long_as_the_gateway_answers_with_2xx(): void
    {
        Cache::put(self::CACHE_KEY, 'msg-7');
        Http::fake(['flash.test/*' => Http::response(['status' => 'invalid', 'valid' => false], 200)]);

        $this->assertTrue($this->driver()->check($this->clientId(), new Password('000000')));
    }

    public function test_check_treats_a_redirect_as_success_too(): void
    {
        Cache::put(self::CACHE_KEY, 'msg-7');
        Http::fake(['flash.test/*' => Http::response('', 302)]);

        $this->assertTrue($this->driver()->check($this->clientId(), new Password('000000')));
    }
}
