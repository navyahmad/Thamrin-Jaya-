<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Inquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Inquiry> */
class InquiryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(), 'name' => fake()->name(), 'email' => fake()->safeEmail(),
            'subject' => fake()->sentence(), 'message' => fake()->paragraph(), 'status' => 'new',
        ];
    }
}
