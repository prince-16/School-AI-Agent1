<?php

namespace Database\Factories;

use App\Enums\MembershipStatus;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolMembership>
 */
class SchoolMembershipFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'user_id' => User::factory(),
            'status' => MembershipStatus::Active,
        ];
    }
}
