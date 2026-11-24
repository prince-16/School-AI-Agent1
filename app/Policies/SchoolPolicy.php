<?php

namespace App\Policies;

use App\Enums\SchoolPermission;
use App\Models\School;
use App\Models\User;
use App\Services\SchoolContext;

class SchoolPolicy
{
    public function view(User $user, School $school): bool
    {
        return $user->canEnterSchool($school);
    }

    public function update(User $user, School $school): bool
    {
        return $user->hasSchoolPermission(SchoolPermission::SchoolUpdate)
            && app(SchoolContext::class)->id() === $school->id;
    }
}
