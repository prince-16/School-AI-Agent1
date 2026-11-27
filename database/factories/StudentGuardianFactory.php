<?php

namespace Database\Factories;

use App\Enums\GuardianRelationship;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentGuardian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentGuardian>
 */
class StudentGuardianFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'relationship' => GuardianRelationship::Father,
            'is_primary' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (StudentGuardian $link): void {
            if ($link->school_id && $link->student_id && $link->guardian_id) {
                return;
            }

            $student = Student::factory()->create();
            $guardian = Guardian::factory()->create(['school_id' => $student->school_id]);

            $link->school_id = $student->school_id;
            $link->student_id = $student->id;
            $link->guardian_id = $guardian->id;
        });
    }
}
