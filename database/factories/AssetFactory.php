<?php

namespace Database\Factories;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'category' => 'Furniture',
            'asset_name' => 'Ergonomic Desk',
            'tracking_number' => 'DSK-'.$this->faker->unique()->numerify('###'),
            'qr_code_payload' => null,
            'prefix' => 'DSK-',
            'unit_number' => $this->faker->numerify('###'),
            'status' => AssetStatus::Inactive,
            'condition' => AssetCondition::Good,
            'location' => null,
            'assigned_to' => null,
        ];
    }
}
