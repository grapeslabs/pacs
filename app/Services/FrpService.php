<?php

namespace App\Services;

use App\Models\Setting;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class FrpService
{
    private const CONFIG_PATH = 'frp/frpc.toml';

    private const SETTING_KEYS = [
        'frp_uuid',
        'frp_token',
        'frp_subdomain',
        'frp_server_addr',
        'frp_server_port',
        'frp_enabled',
    ];

    private ?Collection $settingsCache = null;

    public function isRegistered(): bool
    {
        return (bool) $this->settingValue('frp_uuid');
    }

    public function isEnabled(): bool
    {
        return (bool) $this->settingValue('frp_enabled');
    }

    public function instanceData(): array
    {
        return [
            'uuid' => $this->settingValue('frp_uuid'),
            'token' => $this->settingValue('frp_token'),
            'subdomain' => $this->settingValue('frp_subdomain'),
        ];
    }

    public function register(): void
    {
        if ($this->isRegistered()) {
            return;
        }

        $data = $this->portal()
            ->post('/reg/instance', [
                'hostname' => gethostname() ?: config('app.name'),
                'version' => config('app.version'),
            ])
            ->throw()
            ->json();

        if (empty($data['uuid']) || empty($data['token'])) {
            throw new Exception('Портал не вернул uuid и токен экземпляра.');
        }

        $this->putSetting('frp_token', $data['token'], encrypted: true);
        $this->putSetting('frp_uuid', $data['uuid']);
        $this->putSetting('frp_subdomain', $data['subdomain'] ?? $data['uuid']);
        $this->putSetting('frp_server_addr', $data['server']['addr'] ?? $this->config('server_addr'));
        $this->putSetting('frp_server_port', $data['server']['port'] ?? $this->config('server_port'));
    }

    public function rotateToken(): string
    {
        $uuid = $this->settingValue('frp_uuid');

        if (!$uuid) {
            throw new Exception('Экземпляр ещё не зарегистрирован на портале.');
        }

        try {
            $data = $this->portal()
                ->post('/reg/instance/' . rawurlencode($uuid) . '/rotate-token', [
                    'token' => $this->settingValue('frp_token'),
                ])
                ->throw()
                ->json();
        } catch (RequestException $e) {
            Log::warning('FRP: token rotation failed', [
                'status' => $e->response->status(),
                'body' => $e->response->body(),
            ]);

            throw new Exception(sprintf(
                'Портал отклонил запрос на сброс токена (HTTP %d).',
                $e->response->status()
            ));
        }

        if (empty($data['token'])) {
            throw new Exception('Портал не вернул новый токен.');
        }

        $this->putSetting('frp_token', $data['token'], encrypted: true);

        if ($this->isEnabled()) {
            $this->writeConfig();
            $this->supervisorctl('restart');
        }

        return $data['token'];
    }

    public function enable(): void
    {
        if (!$this->isRegistered()) {
            throw new Exception('Экземпляр ещё не зарегистрирован на портале, попробуйте позже.');
        }

        $this->writeConfig();
        $this->supervisorctl('start');
        $this->putSetting('frp_enabled', true);
    }

    public function disable(): void
    {
        $this->supervisorctl('stop');
        $this->putSetting('frp_enabled', false);
    }

    private function portal(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) $this->config('portal_url'), '/'))
            ->timeout((int) $this->config('timeout'))
            ->acceptJson();
    }

    private function writeConfig(): void
    {
        $uuid = $this->settingValue('frp_uuid');

        $toml = implode("\n", [
            'serverAddr = ' . $this->tomlString($this->settingValue('frp_server_addr') ?: $this->config('server_addr')),
            'serverPort = ' . (int) ($this->settingValue('frp_server_port') ?: $this->config('server_port')),
            'user = ' . $this->tomlString($uuid),
            '',
            '[auth]',
            'method = "token"',
            '',
            '[metadatas]',
            'token = ' . $this->tomlString($this->settingValue('frp_token')),
            '',
            '[[proxies]]',
            'name = "pacs-web"',
            'type = "http"',
            'localIP = ' . $this->tomlString($this->config('local_host')),
            'localPort = ' . (int) $this->config('local_port'),
            'subdomain = ' . $this->tomlString($this->settingValue('frp_subdomain') ?: $uuid),
            'requestHeaders.set.x-forwarded-proto = "https"',
            '',
        ]);

        Storage::disk('local')->put(self::CONFIG_PATH, $toml);
    }

    private function tomlString(mixed $value): string
    {
        $escaped = str_replace(
            ['\\', '"', "\n", "\r", "\t"],
            ['\\\\', '\\"', '\\n', '\\r', '\\t'],
            (string) $value
        );

        return '"' . $escaped . '"';
    }

    private function supervisorctl(string $action): void
    {
        $command = ['supervisorctl', '-s', (string) $this->config('supervisor_url')];

        if ($user = $this->config('supervisor_user')) {
            array_push($command, '-u', $user);
        }

        if ($pass = $this->config('supervisor_pass')) {
            array_push($command, '-p', $pass);
        }

        array_push($command, $action, 'frpc');

        $result = Process::timeout((int) $this->config('timeout'))->run($command);
        $output = trim($result->errorOutput() ?: $result->output());

        if ($result->successful()
            || str_contains($output, 'already started')
            || str_contains($output, 'not running')
        ) {
            return;
        }

        Log::error('FRP: supervisorctl command failed', ['action' => $action, 'output' => $output]);

        throw new Exception(sprintf(
            'Не удалось %s службу frpc.',
            ['start' => 'запустить', 'stop' => 'остановить', 'restart' => 'перезапустить'][$action] ?? $action
        ));
    }

    private function config(string $key): mixed
    {
        return config("services.frp.$key");
    }

    private function putSetting(string $key, mixed $value, bool $encrypted = false): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['name' => $key, 'group' => Setting::GROUP_INTEGRATION, 'is_encrypted' => $encrypted, 'value' => $value]
        );

        $this->settingsCache = null;
    }

    private function settings(): Collection
    {
        return $this->settingsCache ??= Setting::whereIn('key', self::SETTING_KEYS)->get()->keyBy('key');
    }

    private function settingValue(string $key, mixed $default = null): mixed
    {
        return $this->settings()->get($key)?->value ?? $default;
    }
}
