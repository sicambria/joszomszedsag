<?php

namespace Database\Factories;

use App\UserSupportData;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserSupportDataFactory extends Factory
{
    protected $model = UserSupportData::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\User::factory(),
            'help_request' => 1,
            'has_coronavirus' => 0,
            'is_in_quarantine' => 0,
            'has_chronic_illness' => 0,
            'is_healthy' => 1,
            'description' => fake()->sentence,
            'status' => 2,
            'volunteer_user_id' => null,
            'address_id' => null,
        ];
    }
}
