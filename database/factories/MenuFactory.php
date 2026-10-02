<?php

namespace Database\Factories;

use App\Models\Menu;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Menu> */
class MenuFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(), 'key' => fake()->unique()->slug(2),
            'name' => 'Navigasi', 'is_active' => true,
        ];
    }
}
