<?php

namespace App\Policies;

use App\Enums\SchoolPermission;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\SchoolContext;

class SchoolClassPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasSchoolPermission(SchoolPermission::ClassesView);
    }

    public function view(User $user, SchoolClass $schoolClass): bool
    {
        return $this->viewAny($user) && app(SchoolContext::class)->id() === $schoolClass->school_id;
    }

    public function create(User $user): bool
    {
        return $user->hasSchoolPermission(SchoolPermission::ClassesManage);
    }

    public function update(User $user, SchoolClass $schoolClass): bool
    {
        return $this->create($user) && app(SchoolContext::class)->id() === $schoolClass->school_id;
    }
}
