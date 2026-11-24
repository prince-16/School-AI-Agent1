<?php

namespace App\Livewire\Schools;

use App\Models\School;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Choose a school')]
class SelectSchool extends Component
{
    public function select(int $schoolId): void
    {
        $school = School::query()->findOrFail($schoolId);

        abort_unless(Auth::user()->canEnterSchool($school), 403);

        session(['current_school_id' => $school->id]);

        $this->redirect(route('dashboard'), navigate: false);
    }

    public function render()
    {
        $user = Auth::user();

        $schools = $user->is_platform_admin
            ? School::query()->orderBy('name')->orderBy('id')->get()
            : School::query()
                ->whereIn('id', $user->memberships()->active()->pluck('school_id'))
                ->orderBy('name')
                ->orderBy('id')
                ->get();

        return view('livewire.schools.select-school', [
            'schools' => $schools,
        ]);
    }
}
