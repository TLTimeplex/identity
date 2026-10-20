<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\ApplicationCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
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
            'description' => $this->faker->sentence(),
            'url' => $this->faker->url(),
            'icon' => $this->faker->word(),
            'is_active' => $this->faker->boolean(),
            'category_id' => ApplicationCategory::factory(),
            'required_permission' => null,
            'hydra_client_id' => $this->faker->uuid(),
        ];
    }

    /**
     * Indicate that the application is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the application is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the application requires a specific permission.
     */
    public function requiresPermission(string $permission): static
    {
        return $this->state(fn (array $attributes) => [
            'required_permission' => $permission,
        ]);
    }
}
