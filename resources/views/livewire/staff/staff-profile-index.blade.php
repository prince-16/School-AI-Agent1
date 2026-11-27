<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Staff</h1>
        <p class="mt-1 text-sm text-slate-500">A staff profile adds an employee code to someone who is already a member of this school.</p>
    </div>

    @if ($statusMessage)
        <p class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ $statusMessage }}</p>
    @endif

    @can('create', App\Models\StaffProfile::class)
        <form wire:submit="save" class="space-y-4 rounded-lg border border-slate-200 bg-white p-4">
            <h2 class="text-sm font-semibold text-slate-900">Add a staff profile</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="user_id" class="block text-sm font-medium text-slate-700">Member</label>
                    <select id="user_id" wire:model="user_id" class="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="">Choose a member</option>
                        @foreach ($members as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>
                <x-form.field name="employee_code" label="Employee code" wire:model="employee_code" />
                <x-form.field name="designation" label="Designation" wire:model="designation" />
            </div>
            <x-form.button>Save staff profile</x-form.button>
        </form>
    @endcan

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-slate-200 text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Employee code</th>
                    <th class="px-4 py-3 font-medium">Name</th>
                    <th class="px-4 py-3 font-medium">Designation</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($profiles as $profile)
                    <tr class="border-b border-slate-100 last:border-0">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $profile->employee_code }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $profile->user->name }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $profile->designation }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-slate-500">No staff profiles yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
