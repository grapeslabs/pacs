<?php

namespace App\Http\Requests\Api\V1;

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
        $id = $this->route('key') ?? $this->route('id');

        return [
            'key' => ['sometimes', 'string', 'max:255', Rule::unique('keys', 'key')->ignore($id)],
            'type' => ['sometimes', 'string', 'max:255'],
            'person_id' => ['sometimes', 'integer', 'exists:person,id'],
        ];
    }
}
