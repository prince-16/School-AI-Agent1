<?php

namespace App\Services;

use App\Models\School;
use Spatie\Permission\PermissionRegistrar;

class SchoolContext
{
    private ?int $schoolId = null;

    private ?School $resolvedSchool = null;

    private bool $bypassed = false;

    public function id(): ?int
    {
        return $this->schoolId;
    }

    public function school(): ?School
    {
        if ($this->schoolId === null) {
            return null;
        }

        return $this->resolvedSchool ??= School::query()->find($this->schoolId);
    }

    public function isBypassed(): bool
    {
        return $this->bypassed;
    }

    public function set(School|int $school): void
    {
        $this->bypassed = false;
        $this->schoolId = $school instanceof School ? $school->id : $school;
        $this->resolvedSchool = $school instanceof School ? $school : null;

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->schoolId);
    }

    public function forget(): void
    {
        $this->schoolId = null;
        $this->resolvedSchool = null;
        $this->bypassed = false;

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function forSchool(School $school, callable $callback): mixed
    {
        $previousId = $this->schoolId;
        $previousSchool = $this->resolvedSchool;
        $previousBypass = $this->bypassed;
        $previousTeam = app(PermissionRegistrar::class)->getPermissionsTeamId();

        $this->bypassed = false;
        $this->schoolId = $school->id;
        $this->resolvedSchool = $school;
        app(PermissionRegistrar::class)->setPermissionsTeamId($school->id);

        try {
            return $callback();
        } finally {
            $this->schoolId = $previousId;
            $this->resolvedSchool = $previousSchool;
            $this->bypassed = $previousBypass;
            app(PermissionRegistrar::class)->setPermissionsTeamId($previousTeam);
        }
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function bypass(callable $callback): mixed
    {
        $previous = $this->bypassed;
        $this->bypassed = true;

        try {
            return $callback();
        } finally {
            $this->bypassed = $previous;
        }
    }
}
