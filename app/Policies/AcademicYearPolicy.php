<?php

namespace App\Policies;

use App\Enums\SchoolPermission;
use App\Models\AcademicYear;
use App\Models\User;
use App\Services\SchoolContext;

class AcademicYearPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasSchoolPermission(SchoolPermission::AcademicYearsView);
    }

    public function view(User $user, AcademicYear $academicYear): bool
    {
        return $this->viewAny($user) && $this->inCurrentSchool($academicYear);
    }

    public function create(User $user): bool
    {
        return $user->hasSchoolPermission(SchoolPermission::AcademicYearsManage);
    }

    public function update(User $user, AcademicYear $academicYear): bool
    {
        return $this->create($user) && $this->inCurrentSchool($academicYear);
    }

    public function delete(User $user, AcademicYear $academicYear): bool
    {
        return $this->update($user, $academicYear);
    }

    private function inCurrentSchool(AcademicYear $academicYear): bool
    {
        return app(SchoolContext::class)->id() === $academicYear->school_id;
    }
}
