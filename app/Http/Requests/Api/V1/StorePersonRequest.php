<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'certificate_number' => ['nullable', 'string', 'max:255'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'comment' => ['nullable', 'string'],
            'frozen_start' => ['nullable', 'date'],
            'frozen_end' => ['nullable', 'date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
            'photo' => ['nullable'],
            'photo.*' => ['file', 'mimes:jpg,png,jpeg,webp'],
        ];
    }

    public function messages(): array
    {
        return [
            'birth_date.before_or_equal' => 'Дата рождения не может быть в будущем',
            'first_name.required' => 'Поле "Имя" обязательно для заполнения',
            'last_name.required' => 'Поле "Фамилия" обязательно для заполнения',
            'photo.*.file' => 'Недопустимый формат файла',
            'photo.*.mimes' => 'Допустимые форматы: JPG, JPEG, PNG, WEBP',
        ];
    }
}
