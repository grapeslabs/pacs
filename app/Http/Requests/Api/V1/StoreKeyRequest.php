<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Key;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:255', 'regex:/^[0-9A-Za-z-]+$/', Rule::unique('keys', 'key')->withoutTrashed()],
            'type' =>['required', Rule::in(Key::TYPES)],
            'person_id' => ['required', 'integer', 'exists:person,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.regex' => 'Ключ может содержать только латинские буквы, цифры и дефис',
        ];
    }
}
