<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DownloadStatusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'requestId' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{1,64}$/'],
        ];
    }

    public function attributes()
    {
        return [
            'requestId' => 'номер запроса',
        ];
    }
}
