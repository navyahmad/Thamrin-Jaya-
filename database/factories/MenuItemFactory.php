<?php

namespace Database\Factories;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MenuItem> */
class MenuItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'menu_id' => Menu::factory(), 'key' => fake()->unique()->slug(2),
            'label' => fake()->words(2, true), 'url' => '/#about',
            'type' => 'link', 'sort_order' => 0, 'is_active' => true,
        ];
    }
}
