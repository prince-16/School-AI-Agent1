<?php

namespace Tests\Feature;

use App\Actions\Schools\AddSchoolMember;
use App\Enums\SchoolRole;
use App\Livewire\Platform\PlatformSchools;
use App\Livewire\Schools\SelectSchool;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;
use App\Services\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class SchoolIsolationTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_a_school_admin_cannot_open_another_schools_academic_year(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();
        $admin = User::factory()->memberOf($school, SchoolRole::Admin)->create();
        $ownYear = AcademicYear::factory()->create([
            'school_id' => $school->id,
            'name' => 'Own year',
        ]);
        $otherYear = AcademicYear::factory()->create([
            'school_id' => $other->id,
            'name' => 'Other year',
        ]);

        $this->enterSchool($admin, $school)
            ->get(route('academic-years.edit', $ownYear))
            ->assertOk()
            ->assertSee('Own year');

        $this->enterSchool($admin, $school)
            ->get(route('academic-years.edit', $otherYear))
            ->assertNotFound();
    }

    public function test_a_member_cannot_switch_into_a_school_they_do_not_belong_to(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create(['name' => 'Other Academy']);
        $admin = User::factory()->memberOf($school, SchoolRole::Admin)->create();
        $this->enterSchool($admin, $school);

        Livewire::test(SelectSchool::class)
            ->call('select', $other->id)
            ->assertForbidden();

        $this->assertSame($school->id, session('current_school_id'));
    }

    public function test_roles_stay_inside_the_school_that_granted_them(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();
        $user = User::factory()->memberOf($school, SchoolRole::Admin)->create();
        app(AddSchoolMember::class)->handle($other, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => SchoolRole::Teacher,
        ]);

        $this->enterSchool($user, $school);
        $this->assertTrue($user->fresh()->can('create', AcademicYear::class));

        $this->enterSchool($user, $other);
        $this->assertFalse($user->fresh()->can('create', AcademicYear::class));
        $this->assertTrue($user->fresh()->can('viewAny', AcademicYear::class));
    }

    public function test_a_platform_admin_can_enter_a_school_and_still_cannot_see_the_other_school(): void
    {
        $school = School::factory()->create(['name' => 'North School']);
        $other = School::factory()->create(['name' => 'South School']);
        $year = AcademicYear::factory()->create([
            'school_id' => $other->id,
            'name' => 'South year',
        ]);
        $admin = User::factory()->platformAdmin()->create();

        Livewire::actingAs($admin)
            ->test(PlatformSchools::class)
            ->call('enter', $school->id)
            ->assertRedirect(route('dashboard'));

        $this->enterSchool($admin, $school)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('North School')
            ->assertDontSee('South year');

        $this->get(route('academic-years.edit', $year))->assertNotFound();
    }

    public function test_a_teacher_cannot_open_platform_schools(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->memberOf($school, SchoolRole::Teacher)->create();

        $this->enterSchool($teacher, $school)
            ->get(route('platform.schools.index'))
            ->assertForbidden();
    }

    public function test_a_platform_admin_can_create_a_school_and_its_first_administrator(): void
    {
        $admin = User::factory()->platformAdmin()->create([
            'email' => 'platform@school.test',
        ]);

        Livewire::actingAs($admin)
            ->test(PlatformSchools::class)
            ->set('name', 'Greenwood High')
            ->set('code', 'gwh')
            ->set('timezone', 'Asia/Kolkata')
            ->set('admin_name', 'Platform Admin')
            ->set('admin_email', 'platform@school.test')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('School created.');

        $school = School::query()->where('code', 'GWH')->first();

        $this->assertNotNull($school);
        $this->assertSame('Greenwood High', $school->name);
        $this->assertTrue($admin->fresh()->canEnterSchool($school));
        app(SchoolContext::class)->set($school);
        $this->assertTrue($admin->fresh()->hasRole(SchoolRole::Admin->value));
    }

    public function test_create_school_rejects_a_duplicate_code(): void
    {
        School::factory()->create(['code' => 'GWH']);
        $admin = User::factory()->platformAdmin()->create();

        Livewire::actingAs($admin)
            ->test(PlatformSchools::class)
            ->set('name', 'Another School')
            ->set('code', 'gwh')
            ->set('timezone', 'Asia/Kolkata')
            ->set('admin_name', 'New Admin')
            ->set('admin_email', 'new-admin@school.test')
            ->set('admin_password', 'password')
            ->set('admin_password_confirmation', 'password')
            ->call('save')
            ->assertHasErrors(['code']);
    }
}
