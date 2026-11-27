<?php

namespace App\Policies;

use App\Enums\SchoolPermission;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\SchoolContext;

class EnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasSchoolPermission(SchoolPermission::StudentsView);
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        return $this->viewAny($user) && app(SchoolContext::class)->id() === $enrollment->school_id;
    }

    public function create(User $user): bool
    {
        return $user->hasSchoolPermission(SchoolPermission::StudentsManage);
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        return $this->create($user) && app(SchoolContext::class)->id() === $enrollment->school_id;
    }
}
