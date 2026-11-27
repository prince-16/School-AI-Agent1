<?php

namespace App\Actions\People;

use App\Enums\SchoolRole;
use App\Exceptions\SchoolRuleException;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\User;
use App\Services\SchoolContext;

class SaveSection
{
    /**
     * @param  array{school_class_id: int, name: string, academic_year_id?: int|null, class_teacher_user_id?: int|null}  $attributes
     */
    public function handle(School $school, array $attributes, ?Section $section = null): Section
    {
        return app(SchoolContext::class)->forSchool($school, function () use ($school, $attributes, $section): Section {
            $class = SchoolClass::query()->find($attributes['school_class_id']);

            if ($class === null) {
                throw new SchoolRuleException('Choose a class before adding a section.');
            }

            $yearId = $attributes['academic_year_id'] ?? $school->current_academic_year_id;
            $year = $yearId === null ? null : AcademicYear::query()->find($yearId);

            if ($year === null) {
                throw new SchoolRuleException('Set a current academic year before adding a section.');
            }

            $name = trim($attributes['name']);

            $duplicate = Section::query()
                ->where('school_class_id', $class->id)
                ->where('academic_year_id', $year->id)
                ->where('name', $name)
                ->when($section, fn ($query) => $query->whereKeyNot($section->getKey()))
                ->exists();

            if ($duplicate) {
                throw new SchoolRuleException('This section already exists for the academic year.');
            }

            $teacherId = $attributes['class_teacher_user_id'] ?? null;
            $teacherId = filled($teacherId) ? (int) $teacherId : null;

            if ($teacherId !== null) {
                $this->assertClassTeacher($school, $teacherId);
            }

            $section ??= new Section;
            $section->school_class_id = $class->id;
            $section->academic_year_id = $year->id;
            $section->name = $name;
            $section->class_teacher_user_id = $teacherId;

            if (! $section->exists) {
                $section->school_id = $school->id;
            }

            $section->save();

            return $section->refresh();
        });
    }

    private function assertClassTeacher(School $school, int $userId): void
    {
        $user = User::query()->find($userId);
        $belongsToSchool = $user !== null && $school->memberships()->active()->where('user_id', $user->id)->exists();

        if (! $belongsToSchool || ! $user->hasRole(SchoolRole::Teacher->value)) {
            throw new SchoolRuleException('The class teacher must be an active teacher at this school.');
        }
    }
}
