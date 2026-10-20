<?php

namespace Database\Factories;

use App\Models\ApplicationCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationCategory>
 */
class ApplicationCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
        ];
    }
}
