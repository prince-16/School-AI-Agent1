<?php

namespace App\Actions\Schools;

use App\Enums\MembershipStatus;
use App\Enums\SchoolRole;
use App\Exceptions\SchoolRuleException;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\User;
use App\Services\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AddSchoolMember
{
    /**
     * @param  array{name: string, email: string, role: SchoolRole|string, password?: string|null}  $attributes
     */
    public function handle(School $school, array $attributes): SchoolMembership
    {
        return app(SchoolContext::class)->forSchool($school, function () use ($school, $attributes): SchoolMembership {
            return DB::transaction(function () use ($school, $attributes): SchoolMembership {
                $email = Str::lower($attributes['email']);
                $user = User::query()->where('email', $email)->first();

                if ($user === null) {
                    if (blank($attributes['password'] ?? null)) {
                        throw new SchoolRuleException('A password is required for a new account.');
                    }

                    $user = User::query()->create([
                        'name' => $attributes['name'],
                        'email' => $email,
                        'password' => $attributes['password'],
                    ]);
                }

                $alreadyMember = $school->memberships()->where('user_id', $user->id)->exists();

                if ($alreadyMember) {
                    throw new SchoolRuleException('This person is already a member of the school.');
                }

                $membership = new SchoolMembership([
                    'status' => MembershipStatus::Active,
                ]);
                $membership->school_id = $school->id;
                $membership->user_id = $user->id;
                $membership->save();

                $role = $attributes['role'] instanceof SchoolRole
                    ? $attributes['role']
                    : SchoolRole::from($attributes['role']);

                $user->syncRoles([$role->value]);

                return $membership->load('user');
            });
        });
    }
}
