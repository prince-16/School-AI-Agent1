<?php

namespace App\Actions\Schools;

use App\Models\School;
use Illuminate\Support\Facades\DB;

class CreateSchool
{
    public function __construct(
        private ProvisionSchoolAccess $access,
        private AddSchoolMember $members,
    ) {}

    /**
     * @param  array{name: string, email: string, role?: string, password?: string|null}  $admin
     */
    public function handle(string $name, string $code, string $timezone, array $admin): School
    {
        return DB::transaction(function () use ($name, $code, $timezone, $admin): School {
            $school = new School;
            $school->name = $name;
            $school->code = $code;
            $school->timezone = $timezone;
            $school->save();

            $this->access->handle($school);

            $admin['role'] ??= 'school_admin';
            $this->members->handle($school, $admin);

            return $school->refresh();
        });
    }
}
