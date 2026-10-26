<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\User;
use App\UserSupportData;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'email_verified_at' => now(),
        'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
        'remember_token' => Str::random(10),
    ];
});

$factory->define(UserSupportData::class, function (Faker $faker) {
    return [
        'user_id' => function () {
            return factory(User::class)->create()->id;
        },
        'help_request' => 1,
        'has_coronavirus' => 0,
        'is_in_quarantine' => 0,
        'has_chronic_illness' => 0,
        'is_healthy' => 1,
        'description' => $faker->sentence,
        'status' => 2,
        'volunteer_user_id' => null,
        'address_id' => null,
    ];
});
