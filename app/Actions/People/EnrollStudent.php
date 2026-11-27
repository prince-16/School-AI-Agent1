<?php

namespace App\Actions\People;

use App\Enums\EnrollmentStatus;
use App\Exceptions\SchoolRuleException;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use App\Services\SchoolContext;

class EnrollStudent
{
    /**
     * @param  array{section_id: int, roll_number?: string|null}  $attributes
     */
    public function handle(Student $student, array $attributes): Enrollment
    {
        $school = $student->school()->firstOrFail();

        return app(SchoolContext::class)->forSchool($school, function () use ($student, $attributes): Enrollment {
            $section = Section::query()->find($attributes['section_id']);

            if ($section === null) {
                throw new SchoolRuleException('Choose a section for this enrollment.');
            }

            $existing = Enrollment::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $section->academic_year_id)
                ->first();

            if ($existing !== null && $existing->status === EnrollmentStatus::Enrolled) {
                throw new SchoolRuleException('This student is already enrolled in this academic year.');
            }

            $rollNumber = filled($attributes['roll_number'] ?? null) ? trim((string) $attributes['roll_number']) : null;

            if ($rollNumber !== null) {
                $rollTaken = Enrollment::query()
                    ->where('section_id', $section->id)
                    ->where('roll_number', $rollNumber)
                    ->where('status', EnrollmentStatus::Enrolled)
                    ->when($existing, fn ($query) => $query->whereKeyNot($existing->getKey()))
                    ->exists();

                if ($rollTaken) {
                    throw new SchoolRuleException('This roll number is already used in the section.');
                }
            }

            $enrollment = $existing ?? new Enrollment;
            $enrollment->student_id = $student->id;
            $enrollment->section_id = $section->id;
            $enrollment->academic_year_id = $section->academic_year_id;
            $enrollment->roll_number = $rollNumber;
            $enrollment->status = EnrollmentStatus::Enrolled;

            if (! $enrollment->exists) {
                $enrollment->school_id = $student->school_id;
            }

            $enrollment->save();

            return $enrollment->refresh();
        });
    }

    public function withdraw(Enrollment $enrollment): Enrollment
    {
        $school = $enrollment->school()->firstOrFail();

        return app(SchoolContext::class)->forSchool($school, function () use ($enrollment): Enrollment {
            $enrollment->status = EnrollmentStatus::Withdrawn;
            $enrollment->roll_number = null;
            $enrollment->save();

            return $enrollment->refresh();
        });
    }
}
