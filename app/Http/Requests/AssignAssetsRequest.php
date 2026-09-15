<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignAssetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_name' => ['required', 'string'],
            'category' => ['required', 'string'],
            'department' => ['required', 'string'],
            'employee' => ['nullable', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
