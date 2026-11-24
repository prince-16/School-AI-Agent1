<?php

namespace App\Actions\Schools;

use App\Enums\MembershipStatus;
use App\Enums\SchoolRole;
use App\Exceptions\SchoolRuleException;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\User;
use App\Services\SchoolContext;

class ManageSchoolMembership
{
    public function changeRole(SchoolMembership $membership, SchoolRole $role): void
    {
        $this->withinSchool($membership, function () use ($membership, $role): void {
            if ($this->isActiveAdmin($membership) && $role !== SchoolRole::Admin) {
                $this->assertAnotherActiveAdmin($membership->school, $membership->user);
            }

            $membership->user->syncRoles([$role->value]);
        });
    }

    public function suspend(SchoolMembership $membership): void
    {
        $this->withinSchool($membership, function () use ($membership): void {
            if ($this->isActiveAdmin($membership)) {
                $this->assertAnotherActiveAdmin($membership->school, $membership->user);
            }

            $membership->status = MembershipStatus::Suspended;
            $membership->save();
        });
    }

    public function reactivate(SchoolMembership $membership): void
    {
        $this->withinSchool($membership, function () use ($membership): void {
            $membership->status = MembershipStatus::Active;
            $membership->save();
        });
    }

    public function remove(SchoolMembership $membership): void
    {
        $this->withinSchool($membership, function () use ($membership): void {
            if ($this->isActiveAdmin($membership)) {
                $this->assertAnotherActiveAdmin($membership->school, $membership->user);
            }

            $membership->user->syncRoles([]);
            $membership->delete();
        });
    }

    private function withinSchool(SchoolMembership $membership, callable $callback): void
    {
        app(SchoolContext::class)->forSchool($membership->school, $callback);
    }

    private function isActiveAdmin(SchoolMembership $membership): bool
    {
        return $membership->status === MembershipStatus::Active
            && $membership->user->hasRole(SchoolRole::Admin->value);
    }

    private function assertAnotherActiveAdmin(School $school, User $user): void
    {
        $anotherAdmin = User::query()
            ->whereKeyNot($user->id)
            ->role(SchoolRole::Admin->value)
            ->whereHas('memberships', function ($query) use ($school): void {
                $query->where('school_id', $school->id)->active();
            })
            ->exists();

        if (! $anotherAdmin) {
            throw new SchoolRuleException('The school needs at least one active administrator.');
        }
    }
}
