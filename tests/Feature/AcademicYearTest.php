<?php

namespace Tests\Feature;

use App\Actions\AcademicYears\SaveAcademicYear;
use App\Enums\SchoolRole;
use App\Exceptions\SchoolRuleException;
use App\Livewire\AcademicYears\AcademicYearIndex;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;
use App\Services\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class AcademicYearTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_an_empty_academic_year_form_explains_each_required_field(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $this->enterSchool($admin, $school);

        Livewire::test(AcademicYearIndex::class)
            ->set('name', '')
            ->set('starts_on', '')
            ->set('ends_on', '')
            ->call('save')
            ->assertHasErrors(['name', 'starts_on', 'ends_on'])
            ->assertSee('The name field is required.')
            ->assertSee('The start date field is required.')
            ->assertSee('The end date field is required.');
    }

    public function test_the_end_date_must_be_after_the_start_date(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $this->enterSchool($admin, $school);

        Livewire::test(AcademicYearIndex::class)
            ->set('name', '2026-27')
            ->set('starts_on', '2027-03-31')
            ->set('ends_on', '2026-04-01')
            ->call('save')
            ->assertHasErrors(['ends_on'])
            ->assertSee('The end date must be after the start date.');

        $this->assertDatabaseCount('academic_years', 0);
    }

    public function test_an_admin_can_save_the_current_academic_year(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $this->enterSchool($admin, $school);

        Livewire::test(AcademicYearIndex::class)
            ->set('name', '2026-27')
            ->set('starts_on', '2026-04-01')
            ->set('ends_on', '2027-03-31')
            ->set('make_current', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Academic year saved.');

        $year = AcademicYear::query()->first();

        $this->assertNotNull($year);
        $this->assertSame('2026-27', $year->name);
        $this->assertSame($school->id, $year->school_id);
        $this->assertSame($year->id, $school->refresh()->current_academic_year_id);
    }

    public function test_overlapping_years_are_rejected(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        AcademicYear::factory()->create([
            'school_id' => $school->id,
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
        ]);
        $this->enterSchool($admin, $school);

        Livewire::test(AcademicYearIndex::class)
            ->set('name', 'Overlap')
            ->set('starts_on', '2026-12-01')
            ->set('ends_on', '2027-06-01')
            ->call('save')
            ->assertHasErrors(['starts_on'])
            ->assertSee('This academic year overlaps an existing year.');
    }

    public function test_the_same_dates_are_allowed_in_another_school(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();
        AcademicYear::factory()->create([
            'school_id' => $other->id,
            'name' => '2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
        ]);

        $year = app(SaveAcademicYear::class)->handle($school, [
            'name' => '2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'make_current' => true,
        ]);

        $this->assertSame($school->id, $year->school_id);
        $this->assertSame($year->id, $school->refresh()->current_academic_year_id);
    }

    public function test_the_current_academic_year_cannot_be_deleted(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $year = AcademicYear::factory()->current()->create(['school_id' => $school->id]);
        $this->enterSchool($admin, $school);

        Livewire::test(AcademicYearIndex::class)
            ->call('delete', $year->id)
            ->assertSee('Set another academic year as current before deleting this one.');

        $this->assertModelExists($year);
    }

    public function test_making_another_year_current_replaces_the_previous_one(): void
    {
        [$school, $admin] = $this->schoolAdmin();
        $first = AcademicYear::factory()->current()->create([
            'school_id' => $school->id,
            'name' => '2025-26',
            'starts_on' => '2025-04-01',
            'ends_on' => '2026-03-31',
        ]);
        $second = AcademicYear::factory()->create([
            'school_id' => $school->id,
            'name' => '2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
        ]);
        $this->enterSchool($admin, $school);

        Livewire::test(AcademicYearIndex::class)->call('makeCurrent', $second->id);

        $this->assertSame($second->id, $school->refresh()->current_academic_year_id);
        $this->assertNotSame($first->id, $school->current_academic_year_id);
    }

    #[DataProvider('rolesWithoutManagement')]
    public function test_roles_without_management_can_view_but_not_change_years(SchoolRole $role): void
    {
        $school = School::factory()->create();
        $user = User::factory()->memberOf($school, $role)->create();
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        app(SchoolContext::class)->set($school);

        $this->assertTrue($user->can('viewAny', AcademicYear::class));
        $this->assertFalse($user->can('create', AcademicYear::class));
        $this->assertFalse($user->can('update', $year));
        $this->assertFalse($user->can('delete', $year));
    }

    public function test_a_teacher_cannot_save_an_academic_year(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->memberOf($school, SchoolRole::Teacher)->create();
        $this->enterSchool($teacher, $school);

        Livewire::test(AcademicYearIndex::class)
            ->set('name', '2026-27')
            ->set('starts_on', '2026-04-01')
            ->set('ends_on', '2027-03-31')
            ->call('save')
            ->assertForbidden();
    }

    public function test_a_teacher_cannot_open_the_edit_page(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->memberOf($school, SchoolRole::Teacher)->create();
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);

        $this->enterSchool($teacher, $school)
            ->get(route('academic-years.edit', $year))
            ->assertForbidden();
    }

    public function test_records_are_hidden_when_no_school_is_selected(): void
    {
        $school = School::factory()->create();
        AcademicYear::factory()->create(['school_id' => $school->id]);

        $this->assertSame(0, AcademicYear::query()->count());
        $this->assertSame(1, app(SchoolContext::class)->bypass(
            fn (): int => AcademicYear::query()->count(),
        ));
    }

    public function test_a_year_cannot_be_saved_for_another_school_from_the_current_context(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();
        app(SchoolContext::class)->set($school);

        $this->expectException(SchoolRuleException::class);

        $year = new AcademicYear([
            'name' => '2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
        ]);
        $year->school_id = $other->id;
        $year->save();
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
