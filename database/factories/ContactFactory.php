<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->firstName('male'),
            'surname' => $this->faker->lastName('male'),
            'patronymic' => $this->faker->optional(0.8)->firstName('male'),
            'telegram' => $this->faker->optional(0.4)->userName(),
            'comment' => $this->faker->optional(0.3)->realText(200),
        ];
    }
}
