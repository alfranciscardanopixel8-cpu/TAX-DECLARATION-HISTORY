<?php

namespace Database\Factories;

use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        return [
            'pin' => fake()->unique()->numerify('###-##-###-###-###'),
            'property_index_number' => fake()->uuid(),
            'lot_number' => 'Lot '.fake()->numberBetween(100, 9999),
            'survey_number' => 'PSD-'.fake()->numerify('##-######'),
            'title_number' => 'T-'.fake()->numerify('#####'),
            'barangay' => 'Sample Barangay',
            'municipality' => 'Sample Municipality',
            'province' => 'Sample Province',
            'classification' => 'Residential',
            'actual_use' => 'Residential Lot',
            'land_area' => 500,
            'unit_of_measure' => 'sqm',
            'status' => 'Active',
            'remarks' => 'Factory property',
        ];
    }
}
