<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DownloadArchiveRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'start_time' => ['required'],
            'end_time' => ['required'],
        ];
    }

    public function attributes()
    {
        return [
            'start_time' => 'начало записи',
            'end_time' => 'конец записи',
        ];
    }
}
