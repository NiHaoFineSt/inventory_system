<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateTagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'string'],
            'asset_name' => ['required', 'string'],
            'prefix' => ['required', 'string'],
            'starting_number' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1'],
            'location' => ['nullable', 'string'],
        ];
    }
}
