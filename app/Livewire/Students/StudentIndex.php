<?php

namespace App\Livewire\Students;

use App\Actions\People\SaveStudent;
use App\Enums\EnrollmentStatus;
use App\Enums\Gender;
use App\Exceptions\SchoolRuleException;
use App\Models\Student;
use App\Services\SchoolContext;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Students')]
class StudentIndex extends Component
{
    public string $search = '';

    public string $admission_number = '';

    public string $first_name = '';

    public string $last_name = '';

    public string $date_of_birth = '';

    public string $gender = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Student::class);
    }

    public function save(SaveStudent $save): void
    {
        $this->authorize('create', Student::class);

        $validated = $this->validate([
            'admission_number' => ['required', 'string', 'max:30'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
        ]);

        try {
            $student = $save->handle(app(SchoolContext::class)->school(), $validated);
        } catch (SchoolRuleException $exception) {
            throw ValidationException::withMessages([
                'admission_number' => $exception->getMessage(),
            ]);
        }

        session()->flash('status', 'Student saved.');

        $this->redirect(route('students.edit', $student), navigate: false);
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
        ];
    }

    public function render()
    {
        $school = app(SchoolContext::class)->school();
        $term = '%'.str_replace(['%', '_'], '', $this->search).'%';
        $currentYearId = $school?->current_academic_year_id;

        $students = Student::query()
            ->with(['enrollments' => function ($query) use ($currentYearId): void {
                $query->where('status', EnrollmentStatus::Enrolled)
                    ->when($currentYearId, fn ($query) => $query->where('academic_year_id', $currentYearId))
                    ->with('section.schoolClass');
            }])
            ->when($this->search !== '', function ($query) use ($term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('admission_number', 'like', $term)
                        ->orWhere('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term);
                });
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->orderBy('id')
            ->get();

        return view('livewire.students.student-index', [
            'students' => $students,
            'genders' => Gender::cases(),
        ]);
    }
}
