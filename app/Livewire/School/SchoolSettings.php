<?php

namespace App\Livewire\School;

use App\Models\School as SchoolModel;
use App\Services\SchoolContext;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('School settings')]
class SchoolSettings extends Component
{
    public string $name = '';

    public string $code = '';

    public string $timezone = '';

    public ?string $status = null;

    public function mount(): void
    {
        $school = $this->school();
        $this->authorize('update', $school);

        $this->name = $school->name;
        $this->code = $school->code;
        $this->timezone = $school->timezone;
    }

    public function save(): void
    {
        $school = $this->school();
        $this->authorize('update', $school);

        $this->code = Str::upper(trim($this->code));

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:16', 'alpha_dash', Rule::unique('schools', 'code')->ignore($school->id)],
            'timezone' => ['required', 'timezone'],
        ]);

        $school->update($validated);

        $this->status = 'School settings saved.';
    }

    public function render()
    {
        return view('livewire.school.school-settings');
    }

    private function school(): SchoolModel
    {
        $school = app(SchoolContext::class)->school();

        abort_if($school === null, 404);

        return $school;
    }
}
