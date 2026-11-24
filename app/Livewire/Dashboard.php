<?php

namespace App\Livewire;

use App\Services\SchoolContext;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $school = app(SchoolContext::class)->school();

        return view('livewire.dashboard', [
            'school' => $school,
            'currentYear' => $school?->currentAcademicYear,
            'memberCount' => $school === null ? 0 : $school->memberships()->count(),
            'yearCount' => $school === null ? 0 : $school->academicYears()->count(),
        ]);
    }
}
