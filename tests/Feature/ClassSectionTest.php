<?php

namespace Tests\Feature;

use App\Actions\People\SaveSection;
use App\Actions\People\SaveStaffProfile;
use App\Enums\SchoolRole;
use App\Exceptions\SchoolRuleException;
use App\Livewire\Classes\ClassIndex;
use App\Livewire\Staff\StaffProfileIndex;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class ClassSectionTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_an_empty_class_form_requires_a_name(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $this->enterSchool($admin, $school);

        Livewire::test(ClassIndex::class)
            ->set('class_name', '')
            ->call('saveClass')
            ->assertHasErrors(['class_name'])
            ->assertSee('The class name field is required.');
    }

    public function test_an_admin_can_save_a_class_and_cannot_reuse_the_name(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $this->enterSchool($admin, $school);

        Livewire::test(ClassIndex::class)
            ->set('class_name', '10')
            ->set('sort_order', '10')
            ->call('saveClass')
            ->assertSee('Class saved.');

        Livewire::test(ClassIndex::class)
            ->set('class_name', '10')
            ->call('saveClass')
            ->assertHasErrors(['class_name'])
            ->assertSee('This class already exists.');

        $this->assertSame(1, SchoolClass::query()->count());
    }

    public function test_a_section_needs_an_academic_year_and_cannot_be_duplicated(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $year = AcademicYear::factory()->current()->create(['school_id' => $school->id]);
        $class = SchoolClass::factory()->create(['school_id' => $school->id, 'name' => '10']);
        $this->enterSchool($admin, $school);

        Livewire::test(ClassIndex::class)
            ->set('school_class_id', (string) $class->id)
            ->set('section_name', 'A')
            ->set('academic_year_id', (string) $year->id)
            ->call('saveSection')
            ->assertSee('Section saved.');

        Livewire::test(ClassIndex::class)
            ->set('school_class_id', (string) $class->id)
            ->set('section_name', 'A')
            ->set('academic_year_id', (string) $year->id)
            ->call('saveSection')
            ->assertHasErrors(['section_name'])
            ->assertSee('This section already exists for the academic year.');
    }

    public function test_a_section_cannot_be_added_before_the_school_has_an_academic_year(): void
    {
        $school = School::factory()->create();
        $class = SchoolClass::factory()->create(['school_id' => $school->id, 'name' => '10']);

        $this->expectException(SchoolRuleException::class);
        $this->expectExceptionMessage('Set a current academic year before adding a section.');

        app(SaveSection::class)->handle($school, [
            'school_class_id' => $class->id,
            'name' => 'A',
        ]);
    }

    public function test_the_class_teacher_must_be_an_active_teacher(): void
    {
        $school = School::factory()->create();
        $year = AcademicYear::factory()->current()->create(['school_id' => $school->id]);
        $class = SchoolClass::factory()->create(['school_id' => $school->id, 'name' => '10']);
        $accountant = User::factory()->memberOf($school, SchoolRole::Accountant)->create();
        app(SchoolContext::class)->set($school);

        $this->expectException(SchoolRuleException::class);
        $this->expectExceptionMessage('The class teacher must be an active teacher at this school.');

        app(SaveSection::class)->handle($school, [
            'school_class_id' => $class->id,
            'name' => 'A',
            'academic_year_id' => $year->id,
            'class_teacher_user_id' => $accountant->id,
        ]);
    }

    public function test_a_teacher_can_view_classes_but_cannot_create_one(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->memberOf($school, SchoolRole::Teacher)->create();
        SchoolClass::factory()->create(['school_id' => $school->id, 'name' => '10']);
        $this->enterSchool($teacher, $school);

        $this->get(route('classes.index'))->assertOk()->assertSee('10');

        Livewire::test(ClassIndex::class)
            ->set('class_name', '9')
            ->call('saveClass')
            ->assertForbidden();
    }

    public function test_classes_from_another_school_are_not_listed(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();
        $admin = User::factory()->memberOf($school, SchoolRole::Admin)->create();
        SchoolClass::factory()->create(['school_id' => $school->id, 'name' => '10']);
        SchoolClass::factory()->create(['school_id' => $other->id, 'name' => 'Hidden Class']);
        $this->enterSchool($admin, $school);

        $this->get(route('classes.index'))
            ->assertOk()
            ->assertSee('10')
            ->assertDontSee('Hidden Class');
    }

    public function test_an_admin_can_save_a_staff_profile_once_per_member(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $teacher = User::factory()->memberOf($school, SchoolRole::Teacher)->create();
        $this->enterSchool($admin, $school);

        Livewire::test(StaffProfileIndex::class)
            ->set('user_id', (string) $teacher->id)
            ->set('employee_code', 't-01')
            ->set('designation', 'Teacher')
            ->call('save')
            ->assertSee('Staff profile saved.');

        Livewire::test(StaffProfileIndex::class)
            ->set('user_id', (string) $teacher->id)
            ->set('employee_code', 'T-02')
            ->set('designation', 'Teacher')
            ->call('save')
            ->assertHasErrors(['employee_code'])
            ->assertSee('This member already has a staff profile.');

        $this->expectException(SchoolRuleException::class);
        $this->expectExceptionMessage('This employee code is already used.');

        app(SaveStaffProfile::class)->handle($school, [
            'user_id' => $admin->id,
            'employee_code' => 'T-01',
            'designation' => 'Administrator',
        ]);
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
