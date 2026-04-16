<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'middle_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'birth_date' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'certificate_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'organization_id' => ['sometimes', 'nullable', 'exists:organizations,id'],
            'comment' => ['sometimes', 'nullable', 'string'],
            'frozen_start' => ['sometimes', 'nullable', 'date'],
            'frozen_end' => ['sometimes', 'nullable', 'date'],
            'tags' => ['sometimes', 'nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
            'photo' => ['sometimes', 'nullable'],
            'photo.*' => ['file', 'mimes:jpg,png,jpeg,webp'],
        ];
    }
}
