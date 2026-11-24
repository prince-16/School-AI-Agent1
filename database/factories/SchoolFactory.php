<?php

namespace Database\Factories;

use App\Actions\Schools\ProvisionSchoolAccess;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' School',
            'code' => fake()->unique()->bothify('??##'),
            'timezone' => 'Asia/Kolkata',
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (School $school): void {
            app(ProvisionSchoolAccess::class)->handle($school);
        });
    }
}
