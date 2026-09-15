<?php

namespace App\Http\Requests;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_name' => ['required', 'string'],
            'location' => ['nullable', 'string'],
            'status' => ['required', new Enum(AssetStatus::class)],
            'condition' => ['required', new Enum(AssetCondition::class)],
        ];
    }
}
