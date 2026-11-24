<?php

namespace Tests\Concerns;

use App\Models\School;
use App\Models\User;
use App\Services\SchoolContext;

trait InteractsWithSchools
{
    protected function enterSchool(User $user, School $school): static
    {
        app(SchoolContext::class)->set($school);

        return $this->actingAs($user)->withSession([
            'current_school_id' => $school->id,
        ]);
    }
}
