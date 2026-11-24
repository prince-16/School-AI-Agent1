<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => '2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
        ];
    }

    public function current(): static
    {
        return $this->afterCreating(function (AcademicYear $year): void {
            $year->school->makeCurrent($year);
        });
    }
}
