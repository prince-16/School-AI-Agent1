<?php

namespace App\Livewire\Students;

use App\Actions\People\AttachGuardian;
use App\Actions\People\EnrollStudent;
use App\Actions\People\SaveStudent;
use App\Enums\Gender;
use App\Enums\GuardianRelationship;
use App\Enums\StudentStatus;
use App\Exceptions\SchoolRuleException;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Edit student')]
class EditStudent extends Component
{
    public Student $student;

    public string $admission_number = '';

    public string $first_name = '';

    public string $last_name = '';

    public string $date_of_birth = '';

    public string $gender = '';

    public string $status = 'active';

    public string $guardian_name = '';

    public string $guardian_relationship = 'father';

    public string $guardian_phone = '';

    public string $guardian_email = '';

    public bool $guardian_is_primary = true;

    public string $section_id = '';

    public string $roll_number = '';

    public ?string $statusMessage = null;

    public function mount(Student $student): void
    {
        $this->authorize('view', $student);
        $this->student = $student;
        $this->fillFromStudent();
    }

    public function save(SaveStudent $save): void
    {
        $this->authorize('update', $this->student);

        $validated = $this->validate([
            'admission_number' => ['required', 'string', 'max:30'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'status' => ['required', Rule::enum(StudentStatus::class)],
        ]);

        try {
            $this->student = $save->handle($this->student->school, $validated, $this->student);
        } catch (SchoolRuleException $exception) {
            throw ValidationException::withMessages([
                'admission_number' => $exception->getMessage(),
            ]);
        }

        $this->statusMessage = 'Student updated.';
    }

    public function addGuardian(AttachGuardian $attach): void
    {
        $this->authorize('update', $this->student);

        $validated = $this->validate([
            'guardian_name' => ['required', 'string', 'max:255'],
            'guardian_relationship' => ['required', Rule::enum(GuardianRelationship::class)],
            'guardian_phone' => ['nullable', 'string', 'max:20'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
            'guardian_is_primary' => ['boolean'],
        ]);

        try {
            $attach->handle($this->student, [
                'name' => $validated['guardian_name'],
                'relationship' => $validated['guardian_relationship'],
                'phone' => $validated['guardian_phone'] ?? null,
                'email' => $validated['guardian_email'] ?? null,
                'is_primary' => $validated['guardian_is_primary'],
            ]);
        } catch (SchoolRuleException $exception) {
            throw ValidationException::withMessages([
                'guardian_email' => $exception->getMessage(),
            ]);
        }

        $this->reset('guardian_name', 'guardian_phone', 'guardian_email');
        $this->guardian_relationship = GuardianRelationship::Father->value;
        $this->guardian_is_primary = false;
        $this->statusMessage = 'Guardian added.';
    }

    public function enroll(EnrollStudent $enroll): void
    {
        $this->authorize('create', Enrollment::class);

        $validated = $this->validate([
            'section_id' => ['required', 'integer'],
            'roll_number' => ['nullable', 'string', 'max:20'],
        ]);

        try {
            $enroll->handle($this->student, [
                'section_id' => (int) $validated['section_id'],
                'roll_number' => $validated['roll_number'] ?? null,
            ]);
        } catch (SchoolRuleException $exception) {
            throw ValidationException::withMessages([
                'section_id' => $exception->getMessage(),
            ]);
        }

        $this->reset('section_id', 'roll_number');
        $this->statusMessage = 'Student enrolled.';
    }

    public function withdraw(int $enrollmentId, EnrollStudent $enroll): void
    {
        $enrollment = $this->student->enrollments()->whereKey($enrollmentId)->firstOrFail();
        $this->authorize('update', $enrollment);
        $enroll->withdraw($enrollment);
        $this->statusMessage = 'Enrollment withdrawn.';
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'admission_number' => 'admission number',
            'first_name' => 'first name',
            'last_name' => 'last name',
            'date_of_birth' => 'date of birth',
            'guardian_name' => 'guardian name',
            'guardian_relationship' => 'relationship',
            'guardian_phone' => 'phone',
            'guardian_email' => 'email',
            'section_id' => 'section',
            'roll_number' => 'roll number',
        ];
    }

    public function render()
    {
        $school = $this->student->school;
        $yearId = $school->current_academic_year_id;

        $sections = $yearId === null
            ? collect()
            : Section::query()
                ->with('schoolClass')
                ->where('academic_year_id', $yearId)
                ->get()
                ->sortBy(fn (Section $section): string => $section->label())
                ->values();

        return view('livewire.students.edit-student', [
            'genders' => Gender::cases(),
            'statuses' => StudentStatus::cases(),
            'relationships' => GuardianRelationship::cases(),
            'guardians' => $this->student->guardians()->with('guardian')->orderByDesc('is_primary')->orderBy('id')->get(),
            'enrollments' => $this->student->enrollments()->with('section.schoolClass', 'academicYear')->orderByDesc('id')->get(),
            'sections' => $sections,
            'currentYearName' => $school->currentAcademicYear?->name,
        ]);
    }

    private function fillFromStudent(): void
    {
        $this->admission_number = $this->student->admission_number;
        $this->first_name = $this->student->first_name;
        $this->last_name = $this->student->last_name ?? '';
        $this->date_of_birth = $this->student->date_of_birth?->toDateString() ?? '';
        $this->gender = $this->student->gender?->value ?? '';
        $this->status = $this->student->status->value;
    }
}
