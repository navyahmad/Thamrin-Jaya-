<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Site> */
class SiteFactory extends Factory
{
    public function group(): static
    {
        return $this->state(fn (): array => ['company_id' => null, 'slug' => 'group', 'template_key' => 'group-gateway']);
    }

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'slug' => fake()->unique()->slug(2), 'name' => fake()->company(),
            'template_key' => 'default', 'is_active' => true,
        ];
    }
}
