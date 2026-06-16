<?php

namespace App\Actuators\Drivers;

use App\Models\ActuatorDevice;
use App\MoonShine\Fields\CustomText;
use Illuminate\Support\Facades\Http;

class HttpDriver extends AbstractActuatorDriver
{
    public static function key(): string
    {
        return 'http';
    }

    public static function title(): string
    {
        return 'HTTP / REST';
    }

    public function fields(): array
    {
        return [
            $this->wire(CustomText::make('Базовый URL', 'base_url')->url()),
            $this->wire(CustomText::make('Канал', 'channel')->default('0')),
            $this->wire(CustomText::make('URL открытия', 'open_url')
                ->hint('Например: {base_url}/relay/{channel}?turn=on')),
            $this->wire(CustomText::make('URL закрытия', 'close_url')
                ->hint('Например: {base_url}/relay/{channel}?turn=off')),
            $this->wire(CustomText::make('HTTP-метод', 'method')->default('GET')
                ->hint('GET или POST')),
        ];
    }

    protected function rawRules(): array
    {
        return [
            'base_url'  => ['required', 'url', 'max:1000'],
            'channel'   => ['nullable', 'string', 'max:50'],
            'open_url'  => ['required', 'string', 'max:1000'],
            'close_url' => ['required', 'string', 'max:1000'],
            'method'    => ['nullable', 'string', 'in:GET,POST,get,post'],
        ];
    }

    public function test(ActuatorDevice $device): bool
    {
        Http::timeout(5)->get((string) $this->setting($device, 'base_url'));
        return true;
    }

    public function open(ActuatorDevice $device): void
    {
        $this->request($device, (string) $this->setting($device, 'open_url'));
    }

    public function close(ActuatorDevice $device): void
    {
        $this->request($device, (string) $this->setting($device, 'close_url'));
    }

    private function request(ActuatorDevice $device, string $template): void
    {
        $url = strtr($template, [
            '{base_url}' => (string) $this->setting($device, 'base_url'),
            '{channel}'  => (string) $this->setting($device, 'channel', '0'),
        ]);

        $method = strtoupper((string) $this->setting($device, 'method', 'GET'));

        $response = Http::timeout(10)->send($method, $url);
        $response->throw();
    }
}
