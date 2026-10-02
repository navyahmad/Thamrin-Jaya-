<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PageSection> */
class PageSectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'page_id' => Page::factory(), 'key' => fake()->unique()->slug(2),
            'type' => 'content', 'title' => fake()->sentence(3), 'body' => fake()->paragraph(),
            'is_active' => true, 'sort_order' => 0,
        ];
    }
}
