<?php

namespace App\Livewire\AcademicYears;

use App\Actions\AcademicYears\SaveAcademicYear;
use App\Exceptions\SchoolRuleException;
use App\Models\AcademicYear;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Edit academic year')]
class EditAcademicYear extends Component
{
    public AcademicYear $academicYear;

    public string $name = '';

    public string $starts_on = '';

    public string $ends_on = '';

    public function mount(AcademicYear $academicYear): void
    {
        $this->authorize('update', $academicYear);

        $this->academicYear = $academicYear;
        $this->name = $academicYear->name;
        $this->starts_on = $academicYear->starts_on->toDateString();
        $this->ends_on = $academicYear->ends_on->toDateString();
    }

    public function save(SaveAcademicYear $save): void
    {
        $this->authorize('update', $this->academicYear);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
        ], [
            'ends_on.after' => 'The end date must be after the start date.',
        ]);

        try {
            $save->handle($this->academicYear->school, $validated, $this->academicYear);
        } catch (SchoolRuleException $exception) {
            throw ValidationException::withMessages([
                'starts_on' => $exception->getMessage(),
            ]);
        }

        session()->flash('status', 'Academic year updated.');

        $this->redirect(route('academic-years.index'), navigate: false);
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
        return view('livewire.academic-years.edit-academic-year');
    }
}
