<?php

namespace App\Actuators\Drivers;

use App\Actuators\Exceptions\ActuatorException;
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
                ->hint('Например: {base_url}/relay/{channel}?turn=on. Длительности открытия у этого драйвера нет: реле должно закрываться само (аппаратный таймер) либо подключаться параллельно кнопке открытия. Сервер отправляет только команду открытия')),
            $this->wire(CustomText::make('URL закрытия', 'close_url')
                ->hint('Например: {base_url}/relay/{channel}?turn=off. Необязателен: используется только кнопкой «Закрыть» в админке, из распознавания ГРЗ не вызывается')),
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
            'close_url' => ['nullable', 'string', 'max:1000'],
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
        $template = (string) $this->setting($device, 'close_url');

        if (trim($template) === '') {
            throw new ActuatorException('URL закрытия не задан: реле закрывается самостоятельно');
        }

        $this->request($device, $template);
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
