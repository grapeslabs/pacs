<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DownloadArchiveRequest extends FormRequest
{
    private const MAX_DURATION = 86400;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'start_time' => ['required', 'integer', 'min:0'],
            'end_time' => ['required', 'integer', 'gt:start_time', 'lte:' . ((int) $this->input('start_time') + self::MAX_DURATION)],
        ];
    }

    public function attributes()
    {
        return [
            'start_time' => 'начало записи',
            'end_time' => 'конец записи',
        ];
    }

    public function messages()
    {
        return [
            'end_time.lte' => 'Длительность фрагмента не может превышать 24 часа',
        ];
    }
}
