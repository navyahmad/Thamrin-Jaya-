<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ProcessStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProcessStep> */
class ProcessStepFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(), 'title' => fake()->sentence(3), 'description' => fake()->paragraph(), 'sort_order' => 1,
        ];
    }
}
