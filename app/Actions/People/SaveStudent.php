<?php

namespace App\Actions\People;

use App\Enums\Gender;
use App\Enums\StudentStatus;
use App\Exceptions\SchoolRuleException;
use App\Models\School;
use App\Models\Student;
use App\Services\SchoolContext;
use Illuminate\Support\Str;

class SaveStudent
{
    /**
     * @param  array{admission_number: string, first_name: string, last_name?: string|null, date_of_birth?: string|null, gender?: string|null, status?: string}  $attributes
     */
    public function handle(School $school, array $attributes, ?Student $student = null): Student
    {
        return app(SchoolContext::class)->forSchool($school, function () use ($school, $attributes, $student): Student {
            $admissionNumber = Str::upper(trim($attributes['admission_number']));

            $duplicate = Student::query()
                ->where('admission_number', $admissionNumber)
                ->when($student, fn ($query) => $query->whereKeyNot($student->getKey()))
                ->exists();

            if ($duplicate) {
                throw new SchoolRuleException('This admission number is already used.');
            }

            $student ??= new Student;
            $student->admission_number = $admissionNumber;
            $student->first_name = $attributes['first_name'];
            $student->last_name = $attributes['last_name'] ?? null;
            $student->date_of_birth = $attributes['date_of_birth'] ?? null;
            $student->gender = filled($attributes['gender'] ?? null) ? Gender::from($attributes['gender']) : null;
            $student->status = StudentStatus::from($attributes['status'] ?? StudentStatus::Active->value);

            if (! $student->exists) {
                $student->school_id = $school->id;
            }

            $student->save();

            return $student->refresh();
        });
    }
}
