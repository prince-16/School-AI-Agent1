<?php

namespace Tests\Feature;

use App\Actions\Schools\AddSchoolMember;
use App\Enums\MembershipStatus;
use App\Enums\SchoolRole;
use App\Livewire\Members\MemberIndex;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\User;
use App\Services\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class MembershipTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_an_admin_can_add_a_new_member(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $this->enterSchool($admin, $school);

        Livewire::test(MemberIndex::class)
            ->set('name', 'Tarun Sharma')
            ->set('email', 'tarun@school.test')
            ->set('role', SchoolRole::Teacher->value)
            ->set('password', 'password')
            ->set('password_confirmation', 'secret-pass')
            ->call('add')
            ->assertHasErrors(['password'])
            ->assertSee('The password field confirmation does not match.');

        Livewire::test(MemberIndex::class)
            ->set('name', 'Tarun Sharma')
            ->set('email', 'tarun@school.test')
            ->set('role', SchoolRole::Teacher->value)
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('add')
            ->assertHasNoErrors();

        $teacher = User::query()->where('email', 'tarun@school.test')->first();

        $this->assertNotNull($teacher);
        $this->assertFalse($teacher->is_platform_admin);
        $this->assertTrue($teacher->hasRole(SchoolRole::Teacher->value));
        $this->assertTrue($school->memberships()->where('user_id', $teacher->id)->exists());
    }

    public function test_adding_an_existing_user_does_not_change_their_password_or_grant_platform_access(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $existing = User::factory()->create([
            'name' => 'Existing Person',
            'email' => 'existing@school.test',
            'password' => 'original-password',
        ]);
        $this->enterSchool($admin, $school);

        app(AddSchoolMember::class)->handle($school, [
            'name' => 'Ignored Name',
            'email' => 'existing@school.test',
            'password' => 'new-password',
            'role' => SchoolRole::Accountant->value,
            'is_platform_admin' => true,
        ]);

        $existing->refresh();

        $this->assertTrue(Hash::check('original-password', $existing->password));
        $this->assertFalse($existing->is_platform_admin);
        $this->assertSame('Existing Person', $existing->name);
        $this->assertTrue($existing->hasRole(SchoolRole::Accountant->value));
    }

    public function test_the_last_active_administrator_cannot_be_removed_or_demoted(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $membership = $school->memberships()->where('user_id', $admin->id)->first();
        $this->enterSchool($admin, $school);

        Livewire::test(MemberIndex::class)
            ->call('changeRole', $membership->id, SchoolRole::Teacher->value)
            ->assertSee('The school needs at least one active administrator.');

        Livewire::test(MemberIndex::class)
            ->call('suspend', $membership->id)
            ->assertSee('The school needs at least one active administrator.');

        Livewire::test(MemberIndex::class)
            ->call('remove', $membership->id)
            ->assertSee('The school needs at least one active administrator.');

        $this->assertSame(MembershipStatus::Active, $membership->refresh()->status);
        $this->assertTrue($admin->fresh()->hasRole(SchoolRole::Admin->value));
    }

    public function test_a_second_administrator_can_change_the_first_admins_role(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $second = User::factory()->memberOf($school, SchoolRole::Admin)->create();
        $membership = $school->memberships()->where('user_id', $admin->id)->first();
        $this->enterSchool($second, $school);

        Livewire::test(MemberIndex::class)
            ->call('changeRole', $membership->id, SchoolRole::Teacher->value)
            ->assertSee('Role updated.');

        $this->assertTrue($admin->fresh()->hasRole(SchoolRole::Teacher->value));
        $this->assertFalse($admin->fresh()->hasRole(SchoolRole::Admin->value));
    }

    #[DataProvider('rolesWithoutMemberAccess')]
    public function test_non_admin_roles_cannot_manage_members(SchoolRole $role): void
    {
        $school = School::factory()->create();
        $user = User::factory()->memberOf($school, $role)->create();
        app(SchoolContext::class)->set($school);

        $this->assertFalse($user->can('viewAny', SchoolMembership::class));
        $this->assertFalse($user->can('create', SchoolMembership::class));
    }

    public function test_a_teacher_cannot_open_the_member_page(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->memberOf($school, SchoolRole::Teacher)->create();

        $this->enterSchool($teacher, $school)
            ->get(route('members.index'))
            ->assertForbidden();
    }

    /**
     * @return array<string, array{0: SchoolRole}>
     */
    public static function rolesWithoutMemberAccess(): array
    {
        return [
            'accountant' => [SchoolRole::Accountant],
            'teacher' => [SchoolRole::Teacher],
            'staff' => [SchoolRole::Staff],
        ];
    }

    /**
     * @return array{0: School, 1: User}
     */
    private function schoolAdmin(): array
    {
        $school = School::factory()->create();
        $admin = User::factory()->memberOf($school, SchoolRole::Admin)->create();

        return [$school, $admin];
    }
}
