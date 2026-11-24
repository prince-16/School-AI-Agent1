<?php

namespace App\Livewire\Members;

use App\Actions\Schools\AddSchoolMember;
use App\Actions\Schools\ManageSchoolMembership;
use App\Enums\SchoolRole;
use App\Exceptions\SchoolRuleException;
use App\Models\SchoolMembership;
use App\Models\User;
use App\Services\SchoolContext;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Members')]
class MemberIndex extends Component
{
    public string $name = '';

    public string $email = '';

    public string $role = 'teacher';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $status = null;

    public ?string $error = null;

    public function mount(): void
    {
        $this->authorize('viewAny', SchoolMembership::class);
    }

    public function add(AddSchoolMember $add): void
    {
        $this->authorize('create', SchoolMembership::class);
        $this->error = null;
        $this->status = null;

        $existing = User::query()->where('email', Str::lower($this->email))->exists();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::enum(SchoolRole::class)],
            'password' => $existing
                ? ['nullable', 'string']
                : ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $add->handle(app(SchoolContext::class)->school(), [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'password' => $validated['password'] ?? null,
            ]);
        } catch (SchoolRuleException $exception) {
            throw ValidationException::withMessages([
                'email' => $exception->getMessage(),
            ]);
        }

        $this->reset('name', 'email', 'password', 'password_confirmation');
        $this->role = SchoolRole::Teacher->value;
        $this->status = 'Member added.';
    }

    public function changeRole(int $membershipId, string $role, ManageSchoolMembership $manage): void
    {
        $membership = SchoolMembership::query()->findOrFail($membershipId);
        $this->authorize('update', $membership);
        $this->error = null;
        $this->status = null;

        try {
            $manage->changeRole($membership, SchoolRole::from($role));
        } catch (SchoolRuleException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->status = 'Role updated.';
    }

    public function suspend(int $membershipId, ManageSchoolMembership $manage): void
    {
        $this->changeStatus($membershipId, fn (SchoolMembership $membership) => $manage->suspend($membership), 'Member suspended.');
    }

    public function reactivate(int $membershipId, ManageSchoolMembership $manage): void
    {
        $this->changeStatus($membershipId, fn (SchoolMembership $membership) => $manage->reactivate($membership), 'Member reactivated.');
    }

    public function remove(int $membershipId, ManageSchoolMembership $manage): void
    {
        $membership = SchoolMembership::query()->findOrFail($membershipId);
        $this->authorize('delete', $membership);
        $this->error = null;
        $this->status = null;

        try {
            $manage->remove($membership);
        } catch (SchoolRuleException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->status = 'Member removed.';
    }

    private function changeStatus(int $membershipId, callable $callback, string $status): void
    {
        $membership = SchoolMembership::query()->findOrFail($membershipId);
        $this->authorize('update', $membership);
        $this->error = null;
        $this->status = null;

        try {
            $callback($membership);
        } catch (SchoolRuleException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->status = $status;
    }

    public function render()
    {
        return view('livewire.members.member-index', [
            'memberships' => SchoolMembership::query()
                ->with('user.roles')
                ->orderBy(
                    User::query()->select('name')->whereColumn('users.id', 'school_memberships.user_id')
                )
                ->orderBy('id')
                ->get(),
            'roles' => SchoolRole::cases(),
        ]);
    }
}
