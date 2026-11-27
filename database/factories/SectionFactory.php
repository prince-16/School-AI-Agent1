<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'A',
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Section $section): void {
            if ($section->school_id && $section->school_class_id && $section->academic_year_id) {
                return;
            }

            $school = School::factory()->create();
            $class = SchoolClass::factory()->create(['school_id' => $school->id, 'name' => '10']);
            $year = AcademicYear::factory()->current()->create(['school_id' => $school->id]);

            $section->school_id = $school->id;
            $section->school_class_id = $class->id;
            $section->academic_year_id = $year->id;
        });
    }
}
