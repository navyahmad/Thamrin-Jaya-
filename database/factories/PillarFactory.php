<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Pillar;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Pillar> */
class PillarFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(), 'title' => fake()->sentence(3), 'description' => fake()->paragraph(), 'sort_order' => 1,
        ];
    }
}
