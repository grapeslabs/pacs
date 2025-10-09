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
            'requestId' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes()
    {
        return [
            'requestId' => 'номер запроса',
        ];
    }
}
