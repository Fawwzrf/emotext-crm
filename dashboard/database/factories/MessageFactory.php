<?php

namespace Database\Factories;

use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(), // If user is needed
            'sender_id' => fake()->phoneNumber(),
            'sender_name' => fake()->name(),
            'message' => fake()->sentence(),
            'sentiment' => fake()->randomElement(['positive', 'negative', 'neutral']),
            'intent' => fake()->randomElement(['order', 'inquiry', 'complaint', 'other']),
            'confidence' => fake()->randomFloat(2, 0.5, 0.99),
            'status' => 'pending',
            'created_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'updated_at' => now(),
        ];
    }
}
