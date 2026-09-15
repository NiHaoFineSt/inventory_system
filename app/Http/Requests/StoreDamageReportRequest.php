<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDamageReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tracking_number' => ['required', 'string'],
            'asset_name' => ['required', 'string'],
            'issue_type' => ['required', 'string'],
            'description' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
