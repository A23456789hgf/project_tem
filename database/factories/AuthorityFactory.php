<?php

namespace Database\Factories;

use App\Models\Authority;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Authority>
 */
class AuthorityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agency_name' => $this->faker->company(),
            'status' => 'Active',
            'is_active' => true,
        ];
    }
}
