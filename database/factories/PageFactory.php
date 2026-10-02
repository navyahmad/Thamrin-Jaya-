<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Page> */
class PageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(), 'slug' => fake()->unique()->slug(2),
            'title' => fake()->sentence(3), 'is_published' => false, 'sort_order' => 0,
        ];
    }
}
