<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'old_name' => ['required', 'string'],
            'new_name' => ['required', 'string', 'max:255'],
        ];
    }
}
