<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Key;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => ['sometimes', 'string', 'max:255', 'regex:/^[0-9A-Za-z-]+$/', Rule::unique('keys', 'key')->ignore($this->route('keyItem'))->withoutTrashed()],
            'type' => ['sometimes', Rule::in(Key::TYPES)],
            'person_id' => ['sometimes', 'integer', 'exists:person,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.regex' => 'Ключ может содержать только латинские буквы, цифры и дефис',
        ];
    }
}
