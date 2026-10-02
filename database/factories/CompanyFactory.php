<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Company> */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(), 'short_name' => fake()->company(),
            'slug' => fake()->unique()->slug(2), 'sector' => 'Packaging',
            'summary' => fake()->paragraph(), 'tagline' => fake()->sentence(),
            'about' => fake()->paragraph(), 'accent' => '#a64632', 'illustration' => 'boxes',
            'is_active' => true, 'is_demo' => true, 'sort_order' => 1,
        ];
    }
}
