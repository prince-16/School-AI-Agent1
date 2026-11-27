<?php

namespace App\Actions\People;

use App\Exceptions\SchoolRuleException;
use App\Models\School;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\SchoolContext;
use Illuminate\Support\Str;

class SaveStaffProfile
{
    /**
     * @param  array{user_id: int, employee_code: string, designation: string}  $attributes
     */
    public function handle(School $school, array $attributes, ?StaffProfile $profile = null): StaffProfile
    {
        return app(SchoolContext::class)->forSchool($school, function () use ($school, $attributes, $profile): StaffProfile {
            $user = User::query()->find($attributes['user_id']);
            $belongsToSchool = $user !== null && $school->memberships()->where('user_id', $user->id)->exists();

            if (! $belongsToSchool) {
                throw new SchoolRuleException('Choose a member of this school.');
            }

            $employeeCode = Str::upper(trim($attributes['employee_code']));

            $duplicateCode = StaffProfile::query()
                ->where('employee_code', $employeeCode)
                ->when($profile, fn ($query) => $query->whereKeyNot($profile->getKey()))
                ->exists();

            if ($duplicateCode) {
                throw new SchoolRuleException('This employee code is already used.');
            }

            $duplicateMember = StaffProfile::query()
                ->where('user_id', $user->id)
                ->when($profile, fn ($query) => $query->whereKeyNot($profile->getKey()))
                ->exists();

            if ($duplicateMember) {
                throw new SchoolRuleException('This member already has a staff profile.');
            }

            $profile ??= new StaffProfile;
            $profile->user_id = $user->id;
            $profile->employee_code = $employeeCode;
            $profile->designation = $attributes['designation'];

            if (! $profile->exists) {
                $profile->school_id = $school->id;
            }

            $profile->save();

            return $profile->refresh();
        });
    }
}
