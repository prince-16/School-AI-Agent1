<?php

namespace App\Policies;

use App\Enums\SchoolPermission;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\SchoolContext;

class StaffProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasSchoolPermission(SchoolPermission::StaffView);
    }

    public function view(User $user, StaffProfile $staffProfile): bool
    {
        return $this->viewAny($user) && app(SchoolContext::class)->id() === $staffProfile->school_id;
    }

    public function create(User $user): bool
    {
        return $user->hasSchoolPermission(SchoolPermission::StaffManage);
    }

    public function update(User $user, StaffProfile $staffProfile): bool
    {
        return $this->create($user) && app(SchoolContext::class)->id() === $staffProfile->school_id;
    }
}
