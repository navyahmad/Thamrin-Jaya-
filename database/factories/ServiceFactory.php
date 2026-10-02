<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Service> */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2), 'name' => fake()->words(2, true),
            'description' => fake()->paragraph(), 'sort_order' => 0, 'is_active' => true,
        ];
    }
}
