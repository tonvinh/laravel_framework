<?php

namespace Database\Factories;

use App\Enums\DemoStatusEnum;
use App\Models\Demo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Demo> */
class DemoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title'   => $this->faker->sentence(4),
            'content' => $this->faker->paragraph(),
            'status'  => $this->faker->randomElement(DemoStatusEnum::cases())->value,
            'user_id' => User::factory(),
        ];
    }
}
