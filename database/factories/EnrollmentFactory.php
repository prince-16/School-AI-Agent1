<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'roll_number' => '1',
            'status' => EnrollmentStatus::Enrolled,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Enrollment $enrollment): void {
            if ($enrollment->school_id && $enrollment->student_id && $enrollment->section_id && $enrollment->academic_year_id) {
                return;
            }

            $section = Section::factory()->create();
            $student = Student::factory()->create(['school_id' => $section->school_id]);

            $enrollment->school_id = $section->school_id;
            $enrollment->student_id = $student->id;
            $enrollment->section_id = $section->id;
            $enrollment->academic_year_id = $section->academic_year_id;
        });
    }
}
