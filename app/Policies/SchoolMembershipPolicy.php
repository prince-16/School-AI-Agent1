<?php

namespace App\Policies;

use App\Enums\SchoolPermission;
use App\Models\SchoolMembership;
use App\Models\User;
use App\Services\SchoolContext;

class SchoolMembershipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasSchoolPermission(SchoolPermission::MembersView);
    }

    public function create(User $user): bool
    {
        return $user->hasSchoolPermission(SchoolPermission::MembersManage);
    }

    public function update(User $user, SchoolMembership $membership): bool
    {
        return $this->create($user) && $this->inCurrentSchool($membership);
    }

    public function delete(User $user, SchoolMembership $membership): bool
    {
        return $this->update($user, $membership);
    }

    private function inCurrentSchool(SchoolMembership $membership): bool
    {
        return app(SchoolContext::class)->id() === $membership->school_id;
    }
}
