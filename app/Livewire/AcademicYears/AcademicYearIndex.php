<?php

namespace App\Livewire\AcademicYears;

use App\Actions\AcademicYears\DeleteAcademicYear;
use App\Actions\AcademicYears\SaveAcademicYear;
use App\Exceptions\SchoolRuleException;
use App\Models\AcademicYear;
use App\Services\SchoolContext;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Academic years')]
class AcademicYearIndex extends Component
{
    public string $name = '';

    public string $starts_on = '';

    public string $ends_on = '';

    public bool $make_current = true;

    public ?string $status = null;

    public ?string $error = null;

    public function mount(): void
    {
        $this->authorize('viewAny', AcademicYear::class);
    }

    public function save(SaveAcademicYear $save): void
    {
        $this->authorize('create', AcademicYear::class);
        $this->error = null;
        $this->status = null;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'make_current' => ['boolean'],
        ], [
            'ends_on.after' => 'The end date must be after the start date.',
        ]);

        try {
            $save->handle(app(SchoolContext::class)->school(), $validated);
        } catch (SchoolRuleException $exception) {
            throw ValidationException::withMessages([
                'starts_on' => $exception->getMessage(),
            ]);
        }

        $this->reset('name', 'starts_on', 'ends_on');
        $this->make_current = true;
        $this->status = 'Academic year saved.';
    }

    public function makeCurrent(int $academicYearId): void
    {
        $year = AcademicYear::query()->findOrFail($academicYearId);
        $this->authorize('update', $year);
        $this->error = null;
        $this->status = null;

        $year->school->makeCurrent($year);
        $this->status = 'Current academic year updated.';
    }

    public function delete(int $academicYearId, DeleteAcademicYear $delete): void
    {
        $year = AcademicYear::query()->findOrFail($academicYearId);
        $this->authorize('delete', $year);
        $this->error = null;
        $this->status = null;

        try {
            $delete->handle($year);
        } catch (SchoolRuleException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->status = 'Academic year deleted.';
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'starts_on' => 'start date',
            'ends_on' => 'end date',
        ];
    }

    public function render()
    {
        return view('livewire.academic-years.academic-year-index', [
            'years' => AcademicYear::query()->orderByDesc('starts_on')->orderByDesc('id')->get(),
            'currentYearId' => app(SchoolContext::class)->school()?->current_academic_year_id,
        ]);
    }
}
