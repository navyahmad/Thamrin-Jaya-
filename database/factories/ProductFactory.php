<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(), 'name' => fake()->words(3, true),
            'category' => 'Packaging', 'description' => fake()->paragraph(),
            'specifications' => 'Material: Paper', 'sort_order' => 1,
        ];
    }
}
