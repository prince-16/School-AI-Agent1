<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffProfile>
 */
class StaffProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'user_id' => User::factory(),
            'employee_code' => fake()->unique()->bothify('EMP-###'),
            'designation' => 'Teacher',
        ];
    }
}
