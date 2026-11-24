<?php

namespace App\Livewire\Platform;

use App\Actions\Schools\CreateSchool;
use App\Enums\SchoolRole;
use App\Exceptions\SchoolRuleException;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Schools')]
class PlatformSchools extends Component
{
    public string $name = '';

    public string $code = '';

    public string $timezone = 'Asia/Kolkata';

    public string $admin_name = '';

    public string $admin_email = '';

    public string $admin_password = '';

    public string $admin_password_confirmation = '';

    public ?string $status = null;

    public function mount(): void
    {
        abort_unless(Auth::user()->is_platform_admin, 403);
    }

    public function save(CreateSchool $create): void
    {
        abort_unless(Auth::user()->is_platform_admin, 403);

        $this->code = Str::upper(trim($this->code));

        $existing = User::query()->where('email', Str::lower($this->admin_email))->exists();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:16', 'alpha_dash', Rule::unique('schools', 'code')],
            'timezone' => ['required', 'timezone'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => $existing
                ? ['nullable', 'string']
                : ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $create->handle(
                $validated['name'],
                $validated['code'],
                $validated['timezone'],
                [
                    'name' => $validated['admin_name'],
                    'email' => $validated['admin_email'],
                    'password' => $validated['admin_password'] ?? null,
                    'role' => SchoolRole::Admin->value,
                ],
            );
        } catch (SchoolRuleException $exception) {
            throw ValidationException::withMessages([
                'admin_email' => $exception->getMessage(),
            ]);
        }

        $this->reset('name', 'code', 'admin_name', 'admin_email', 'admin_password', 'admin_password_confirmation');
        $this->timezone = 'Asia/Kolkata';
        $this->status = 'School created.';
    }

    public function enter(int $schoolId): void
    {
        abort_unless(Auth::user()->is_platform_admin, 403);

        $school = School::query()->findOrFail($schoolId);
        session(['current_school_id' => $school->id]);

        $this->redirect(route('dashboard'), navigate: false);
    }

    public function render()
    {
        return view('livewire.platform.platform-schools', [
            'schools' => School::query()
                ->with('currentAcademicYear')
                ->orderBy('name')
                ->orderBy('id')
                ->get(),
        ]);
    }
}
