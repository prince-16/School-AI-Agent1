<?php

namespace App\Policies;

use App\Enums\SchoolPermission;
use App\Models\Section;
use App\Models\User;
use App\Services\SchoolContext;

class SectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasSchoolPermission(SchoolPermission::ClassesView);
    }

    public function view(User $user, Section $section): bool
    {
        return $this->viewAny($user) && app(SchoolContext::class)->id() === $section->school_id;
    }

    public function create(User $user): bool
    {
        return $user->hasSchoolPermission(SchoolPermission::ClassesManage);
    }

    public function update(User $user, Section $section): bool
    {
        return $this->create($user) && app(SchoolContext::class)->id() === $section->school_id;
    }
}
