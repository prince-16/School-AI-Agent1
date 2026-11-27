<?php

namespace App\Livewire\Classes;

use App\Actions\People\SaveSchoolClass;
use App\Actions\People\SaveSection;
use App\Enums\SchoolRole;
use App\Exceptions\SchoolRuleException;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\User;
use App\Services\SchoolContext;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Classes')]
class ClassIndex extends Component
{
    public string $class_name = '';

    public string $sort_order = '0';

    public string $section_name = '';

    public string $school_class_id = '';

    public string $academic_year_id = '';

    public string $class_teacher_user_id = '';

    public ?string $statusMessage = null;

    public function mount(): void
    {
        $this->authorize('viewAny', SchoolClass::class);
        $this->academic_year_id = (string) (app(SchoolContext::class)->school()?->current_academic_year_id ?? '');
    }

    public function saveClass(SaveSchoolClass $save): void
    {
        $this->authorize('create', SchoolClass::class);
        $this->statusMessage = null;

        $validated = $this->validate([
            'class_name' => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        try {
            $class = $save->handle(app(SchoolContext::class)->school(), [
                'name' => $validated['class_name'],
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
            ]);
        } catch (SchoolRuleException $exception) {
            throw ValidationException::withMessages([
                'class_name' => $exception->getMessage(),
            ]);
        }

        $this->reset('class_name');
        $this->sort_order = '0';
        $this->school_class_id = (string) $class->id;
        $this->statusMessage = 'Class saved.';
    }

    public function saveSection(SaveSection $save): void
    {
        $this->authorize('create', Section::class);
        $this->statusMessage = null;

        $validated = $this->validate([
            'school_class_id' => ['required', 'integer'],
            'section_name' => ['required', 'string', 'max:20'],
            'academic_year_id' => ['required', 'integer'],
            'class_teacher_user_id' => ['nullable', 'integer'],
        ]);

        try {
            $save->handle(app(SchoolContext::class)->school(), [
                'school_class_id' => (int) $validated['school_class_id'],
                'name' => $validated['section_name'],
                'academic_year_id' => (int) $validated['academic_year_id'],
                'class_teacher_user_id' => $validated['class_teacher_user_id'] ?? null,
            ]);
        } catch (SchoolRuleException $exception) {
            throw ValidationException::withMessages([
                'section_name' => $exception->getMessage(),
            ]);
        }

        $this->reset('section_name', 'class_teacher_user_id');
        $this->statusMessage = 'Section saved.';
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'class_name' => 'class name',
            'sort_order' => 'sort order',
            'section_name' => 'section name',
            'school_class_id' => 'class',
            'academic_year_id' => 'academic year',
            'class_teacher_user_id' => 'class teacher',
        ];
    }

    public function render()
    {
        $school = app(SchoolContext::class)->school();
        $yearId = $this->academic_year_id !== '' ? (int) $this->academic_year_id : null;

        $classes = SchoolClass::query()
            ->with(['sections' => function ($query) use ($yearId): void {
                $query->when($yearId, fn ($query) => $query->where('academic_year_id', $yearId))
                    ->with('classTeacher')
                    ->orderBy('name');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $teachers = $school === null
            ? collect()
            : User::query()
                ->role(SchoolRole::Teacher->value)
                ->whereHas('memberships', function ($query) use ($school): void {
                    $query->where('school_id', $school->id)->active();
                })
                ->orderBy('name')
                ->get();

        return view('livewire.classes.class-index', [
            'classes' => $classes,
            'years' => AcademicYear::query()->orderByDesc('starts_on')->orderByDesc('id')->get(),
            'teachers' => $teachers,
        ]);
    }
}
