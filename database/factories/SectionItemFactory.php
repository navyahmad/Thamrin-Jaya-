<?php

namespace Database\Factories;

use App\Models\PageSection;
use App\Models\SectionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SectionItem> */
class SectionItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'page_section_id' => PageSection::factory(), 'key' => fake()->unique()->slug(2),
            'title' => fake()->sentence(3), 'body' => fake()->paragraph(), 'sort_order' => 0, 'is_active' => true,
        ];
    }
}
