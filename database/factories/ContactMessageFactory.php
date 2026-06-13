<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'subject' => fake()->sentence(4),
            'email' => fake()->safeEmail(),
            'message' => fake()->paragraph(),
            'notified_at' => null,
            'notify_attempts' => 0,
        ];
    }

    /** A message the sweep has already delivered. */
    public function notified(): static
    {
        return $this->state(fn () => ['notified_at' => now()]);
    }
}
