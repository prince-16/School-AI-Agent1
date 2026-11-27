<?php

namespace App\Policies;

use App\Enums\SchoolPermission;
use App\Models\Student;
use App\Models\User;
use App\Services\SchoolContext;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasSchoolPermission(SchoolPermission::StudentsView);
    }

    public function view(User $user, Student $student): bool
    {
        return $this->viewAny($user) && $this->inCurrentSchool($student);
    }

    public function create(User $user): bool
    {
        return $user->hasSchoolPermission(SchoolPermission::StudentsManage);
    }

    public function update(User $user, Student $student): bool
    {
        return $this->create($user) && $this->inCurrentSchool($student);
    }

    private function inCurrentSchool(Student $student): bool
    {
        return app(SchoolContext::class)->id() === $student->school_id;
    }
}
