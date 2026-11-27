<?php

namespace App\Livewire\Staff;

use App\Actions\People\SaveStaffProfile;
use App\Exceptions\SchoolRuleException;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\SchoolContext;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Staff')]
class StaffProfileIndex extends Component
{
    public string $user_id = '';

    public string $employee_code = '';

    public string $designation = 'Teacher';

    public ?string $statusMessage = null;

    public function mount(): void
    {
        $this->authorize('viewAny', StaffProfile::class);
    }

    public function save(SaveStaffProfile $save): void
    {
        $this->authorize('create', StaffProfile::class);
        $this->statusMessage = null;

        $validated = $this->validate([
            'user_id' => ['required', 'integer'],
            'employee_code' => ['required', 'string', 'max:30'],
            'designation' => ['required', 'string', 'max:100'],
        ]);

        try {
            $save->handle(app(SchoolContext::class)->school(), $validated);
        } catch (SchoolRuleException $exception) {
            throw ValidationException::withMessages([
                'employee_code' => $exception->getMessage(),
            ]);
        }

        $this->reset('user_id', 'employee_code');
        $this->designation = 'Teacher';
        $this->statusMessage = 'Staff profile saved.';
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'user_id' => 'member',
            'employee_code' => 'employee code',
        ];
    }

    public function render()
    {
        $school = app(SchoolContext::class)->school();

        $members = $school === null
            ? collect()
            : User::query()
                ->whereHas('memberships', function ($query) use ($school): void {
                    $query->where('school_id', $school->id);
                })
                ->orderBy('name')
                ->get();

        return view('livewire.staff.staff-profile-index', [
            'profiles' => StaffProfile::query()->with('user')->orderBy('employee_code')->get(),
            'members' => $members,
        ]);
    }
}
