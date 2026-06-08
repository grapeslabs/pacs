<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class IdentifyPersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grapesva_uuid' => ['required', 'string', 'max:255', 'unique:person'],
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'certificate_number' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
            'photo' => ['nullable'],
            'photo.*' => ['file', 'mimes:jpg,png,jpeg,webp'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'comment' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'grapesva_uuid.required' => 'Идентификатор обязателен',
            'grapesva_uuid.unique' => 'Идентификатор должен быть уникальным',
            'last_name.required' => 'Фамилия обязательна',
            'first_name.required' => 'Имя обязательно',
            'photo.*.mimes' => 'Неверный формат изображения, допустимы: jpg, png, jpeg, webp',
            'tags.*.exists' => 'Один или несколько тегов не найдены',
            'organization_id.exists' => 'Указанная организация не найдена',
            'birth_date.date' => 'Некорректный формат даты рождения',
        ];
    }
}
