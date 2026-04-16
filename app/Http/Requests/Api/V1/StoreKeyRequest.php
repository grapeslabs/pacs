<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:255', 'unique:keys,key'],
            'type' => ['required', 'string', 'max:255'],
            'person_id' => ['required', 'integer', 'exists:person,id'],
        ];
    }
}
