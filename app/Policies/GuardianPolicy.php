<?php

namespace App\Policies;

use App\Enums\SchoolPermission;
use App\Models\Guardian;
use App\Models\User;
use App\Services\SchoolContext;

class GuardianPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasSchoolPermission(SchoolPermission::StudentsView);
    }

    public function view(User $user, Guardian $guardian): bool
    {
        return $this->viewAny($user) && app(SchoolContext::class)->id() === $guardian->school_id;
    }

    public function create(User $user): bool
    {
        return $user->hasSchoolPermission(SchoolPermission::StudentsManage);
    }
}
