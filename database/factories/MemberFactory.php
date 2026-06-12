<?php

namespace RayzenAI\ProjectManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RayzenAI\ProjectManagement\Models\Member;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'title' => $this->faker->optional()->jobTitle(),
            'is_active' => true,
        ];
    }

    public function linkedTo(mixed $user): self
    {
        return $this->state([
            'user_id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(['is_active' => false]);
    }
}
