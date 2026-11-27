<?php

namespace Tests\Feature;

use App\Actions\People\AttachGuardian;
use App\Actions\People\EnrollStudent;
use App\Actions\People\SaveStudent;
use App\Enums\SchoolRole;
use App\Exceptions\SchoolRuleException;
use App\Livewire\Students\EditStudent;
use App\Livewire\Students\StudentIndex;
use App\Models\AcademicYear;
use App\Models\Guardian;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Services\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_an_empty_student_form_explains_each_required_field(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $this->enterSchool($admin, $school);

        Livewire::test(StudentIndex::class)
            ->set('admission_number', '')
            ->set('first_name', '')
            ->call('save')
            ->assertHasErrors(['admission_number', 'first_name'])
            ->assertSee('The admission number field is required.')
            ->assertSee('The first name field is required.');
    }

    public function test_the_date_of_birth_must_be_before_today(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $this->enterSchool($admin, $school);

        Livewire::test(StudentIndex::class)
            ->set('admission_number', 'GWH-1')
            ->set('first_name', 'Tarun')
            ->set('date_of_birth', now()->addDay()->toDateString())
            ->call('save')
            ->assertHasErrors(['date_of_birth'])
            ->assertSee('The date of birth field must be a date before today.');
    }

    public function test_an_admin_can_save_a_student_and_the_name_is_escaped(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $this->enterSchool($admin, $school);

        Livewire::test(StudentIndex::class)
            ->set('admission_number', 'gwh-1')
            ->set('first_name', "<script>alert('xss')</script>")
            ->set('last_name', 'Sharma')
            ->call('save')
            ->assertRedirect();

        $student = Student::query()->first();

        $this->assertNotNull($student);
        $this->assertSame('GWH-1', $student->admission_number);
        $this->assertSame($school->id, $student->school_id);

        $response = $this->get(route('students.edit', $student));
        $response->assertOk();
        $response->assertSee("<script>alert('xss')</script>");
        $response->assertDontSee("<script>alert('xss')</script>", false);
    }

    public function test_an_admission_number_cannot_be_reused_in_the_same_school(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        Student::factory()->create([
            'school_id' => $school->id,
            'admission_number' => 'GWH-1',
        ]);
        $this->enterSchool($admin, $school);

        Livewire::test(StudentIndex::class)
            ->set('admission_number', 'gwh-1')
            ->set('first_name', 'Abhishek')
            ->call('save')
            ->assertHasErrors(['admission_number'])
            ->assertSee('This admission number is already used.');
    }

    public function test_a_student_payload_cannot_move_the_record_to_another_school(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $student = app(SaveStudent::class)->handle($school, [
            'admission_number' => 'GWH-9',
            'first_name' => 'Tarun',
            'school_id' => $other->id,
        ]);

        $this->assertSame($school->id, $student->school_id);
    }

    public function test_students_are_hidden_when_no_school_is_selected(): void
    {
        Student::factory()->create();

        $this->assertSame(0, Student::query()->count());
        $this->assertSame(1, app(SchoolContext::class)->bypass(
            fn (): int => Student::query()->count(),
        ));
    }

    public function test_a_school_admin_cannot_open_another_schools_student(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();
        $admin = User::factory()->memberOf($school, SchoolRole::Admin)->create();
        $own = Student::factory()->create(['school_id' => $school->id, 'first_name' => 'Tarun']);
        $foreign = Student::factory()->create(['school_id' => $other->id, 'first_name' => 'Hidden']);

        $this->enterSchool($admin, $school)
            ->get(route('students.edit', $own))
            ->assertOk()
            ->assertSee('Tarun');

        $this->get(route('students.edit', $foreign))->assertNotFound();
    }

    #[DataProvider('rolesWithoutManagement')]
    public function test_roles_without_management_can_view_but_not_change_students(SchoolRole $role): void
    {
        $school = School::factory()->create();
        $user = User::factory()->memberOf($school, $role)->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        app(SchoolContext::class)->set($school);

        $this->assertTrue($user->can('viewAny', Student::class));
        $this->assertTrue($user->can('view', $student));
        $this->assertFalse($user->can('create', Student::class));
        $this->assertFalse($user->can('update', $student));
    }

    public function test_a_teacher_can_open_the_student_list_but_cannot_save_a_student(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->memberOf($school, SchoolRole::Teacher)->create();
        $this->enterSchool($teacher, $school);

        $this->get(route('students.index'))->assertOk();

        Livewire::test(StudentIndex::class)
            ->set('admission_number', 'GWH-2')
            ->set('first_name', 'Hari')
            ->call('save')
            ->assertForbidden();
    }

    public function test_a_guardian_can_be_shared_by_siblings_and_not_linked_twice(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        app(SchoolContext::class)->set($school);
        $first = app(SaveStudent::class)->handle($school, [
            'admission_number' => 'GWH-1',
            'first_name' => 'Tarun',
        ]);
        $second = app(SaveStudent::class)->handle($school, [
            'admission_number' => 'GWH-2',
            'first_name' => 'Anaya',
        ]);

        app(AttachGuardian::class)->handle($first, [
            'name' => 'Ramesh Sharma',
            'relationship' => 'father',
            'email' => 'ramesh@family.test',
            'is_primary' => true,
        ]);
        app(AttachGuardian::class)->handle($second, [
            'name' => 'Ramesh Sharma',
            'relationship' => 'father',
            'email' => 'ramesh@family.test',
        ]);

        $this->assertSame(1, Guardian::query()->count());
        $this->assertSame(1, $first->guardians()->count());
        $this->assertSame(1, $second->guardians()->count());

        $this->expectException(SchoolRuleException::class);
        $this->expectExceptionMessage('This guardian is already linked to the student.');

        app(AttachGuardian::class)->handle($first, [
            'name' => 'Ramesh Sharma',
            'relationship' => 'father',
            'email' => 'ramesh@family.test',
        ]);
    }

    public function test_a_student_cannot_hold_two_enrollments_in_one_academic_year(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $year = AcademicYear::factory()->current()->create(['school_id' => $school->id]);
        $class = SchoolClass::factory()->create(['school_id' => $school->id, 'name' => '10']);
        $section = Section::factory()->create([
            'school_id' => $school->id,
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'name' => 'A',
        ]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $this->enterSchool($admin, $school);

        Livewire::test(EditStudent::class, ['student' => $student])
            ->set('section_id', (string) $section->id)
            ->set('roll_number', '1')
            ->call('enroll')
            ->assertSee('Student enrolled.')
            ->set('section_id', (string) $section->id)
            ->call('enroll')
            ->assertHasErrors(['section_id'])
            ->assertSee('This student is already enrolled in this academic year.');
    }

    public function test_a_withdrawn_student_can_enroll_again_and_a_roll_number_cannot_be_reused(): void
    {
        $school = School::factory()->create();
        $year = AcademicYear::factory()->current()->create(['school_id' => $school->id]);
        $class = SchoolClass::factory()->create(['school_id' => $school->id, 'name' => '10']);
        $section = Section::factory()->create([
            'school_id' => $school->id,
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'name' => 'A',
        ]);
        $first = Student::factory()->create(['school_id' => $school->id, 'admission_number' => 'GWH-1']);
        $second = Student::factory()->create(['school_id' => $school->id, 'admission_number' => 'GWH-2']);
        app(SchoolContext::class)->set($school);

        $enrollment = app(EnrollStudent::class)->handle($first, [
            'section_id' => $section->id,
            'roll_number' => '4',
        ]);

        try {
            app(EnrollStudent::class)->handle($second, [
                'section_id' => $section->id,
                'roll_number' => '4',
            ]);
            $this->fail('The duplicate roll number was accepted.');
        } catch (SchoolRuleException $exception) {
            $this->assertSame('This roll number is already used in the section.', $exception->getMessage());
        }

        app(EnrollStudent::class)->withdraw($enrollment);

        $again = app(EnrollStudent::class)->handle($first, [
            'section_id' => $section->id,
            'roll_number' => '4',
        ]);

        $this->assertSame($enrollment->id, $again->id);
        $this->assertSame('enrolled', $again->status->value);
    }

    /**
     * @return array<string, array{0: SchoolRole}>
     */
    public static function rolesWithoutManagement(): array
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
