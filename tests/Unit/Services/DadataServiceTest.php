<?php

namespace Tests\Unit\Services;

use App\Services\DadataService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DadataServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.dadata.token', 'test-token');
        Config::set('services.dadata.secret', 'test-secret');
        Config::set('services.dadata.url', 'https://dadata.test/suggest/party');
        Config::set('services.dadata.timeout', 7);
    }

    private function service(): DadataService
    {
        return new DadataService();
    }

    public function test_short_query_returns_empty_without_any_request(): void
    {
        Http::fake();

        $this->assertSame([], $this->service()->searchOrganizations('ab'));

        Http::assertNothingSent();
    }

    public function test_suggestions_are_returned_as_is(): void
    {
        Http::fake(['dadata.test/*' => Http::response([
            'suggestions' => [
                ['value' => 'ООО Ромашка', 'data' => ['inn' => '7701234567']],
            ],
        ], 200)]);

        $result = $this->service()->searchOrganizations('Ромашка');

        $this->assertSame([['value' => 'ООО Ромашка', 'data' => ['inn' => '7701234567']]], $result);
    }

    public function test_request_carries_token_secret_and_payload(): void
    {
        Http::fake(['dadata.test/*' => Http::response(['suggestions' => []], 200)]);

        $this->service()->searchOrganizations('Ромашка', 12);

        Http::assertSent(function (Request $r) {
            $this->assertSame('POST', $r->method());
            $this->assertSame('https://dadata.test/suggest/party', $r->url());
            $this->assertSame('Token test-token', $r->header('Authorization')[0]);
            $this->assertSame('test-secret', $r->header('X-Secret')[0]);
            $this->assertSame('Ромашка', $r['query']);
            $this->assertSame(12, $r['count']);

            return true;
        });
    }

    public function test_default_count_is_five(): void
    {
        Http::fake(['dadata.test/*' => Http::response(['suggestions' => []], 200)]);

        $this->service()->searchOrganizations('Ромашка');

        Http::assertSent(fn (Request $r) => $r['count'] === 5);
    }

    public function test_successful_response_without_suggestions_key_yields_empty_array(): void
    {
        Http::fake(['dadata.test/*' => Http::response(['unexpected' => 'shape'], 200)]);

        $this->assertSame([], $this->service()->searchOrganizations('Ромашка'));
    }

    public function test_http_error_is_swallowed_into_an_empty_array(): void
    {
        Http::fake(['dadata.test/*' => Http::response('Forbidden', 403)]);

        $this->assertSame([], $this->service()->searchOrganizations('Ромашка'));
    }

    public function test_connection_failure_is_swallowed_into_an_empty_array(): void
    {
        Http::fake(['dadata.test/*' => fn () => throw new ConnectionException('refused')]);

        $this->assertSame([], $this->service()->searchOrganizations('Ромашка'));
    }

    public function test_length_guard_counts_bytes_not_characters(): void
    {
        Http::fake(['dadata.test/*' => Http::response(['suggestions' => []], 200)]);

        $this->service()->searchOrganizations('Ро');

        Http::assertSentCount(1);
    }

    public function test_service_builds_with_blank_credentials_from_an_unfilled_env(): void
    {
        Config::set('services.dadata.token', '');
        Config::set('services.dadata.secret', '');

        Http::fake(['dadata.test/*' => Http::response('Unauthorized', 401)]);

        $this->assertSame([], (new DadataService())->searchOrganizations('Ромашка'));
    }
}
