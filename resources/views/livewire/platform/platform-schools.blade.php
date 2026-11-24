<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Schools</h1>
        <p class="mt-1 text-sm text-slate-500">Create a school and its first administrator. Data from one school is never shown inside another.</p>
    </div>

    @if ($status)
        <p class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ $status }}</p>
    @endif

    <form wire:submit="save" class="space-y-4 rounded-lg border border-slate-200 bg-white p-4">
        <h2 class="text-sm font-semibold text-slate-900">New school</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.field name="name" label="School name" wire:model="name" />
            <x-form.field name="code" label="School code" wire:model="code" />
            <x-form.field name="timezone" label="Timezone" wire:model="timezone" />
            <x-form.field name="admin_name" label="Administrator name" wire:model="admin_name" />
            <x-form.field name="admin_email" label="Administrator email" type="email" wire:model="admin_email" />
            <x-form.field name="admin_password" label="Administrator password" type="password" wire:model="admin_password" />
            <x-form.field name="admin_password_confirmation" label="Confirm password" type="password" wire:model="admin_password_confirmation" />
        </div>
        <x-form.button>Create school</x-form.button>
    </form>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-slate-200 text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">School</th>
                    <th class="px-4 py-3 font-medium">Code</th>
                    <th class="px-4 py-3 font-medium">Current year</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($schools as $school)
                    <tr class="border-b border-slate-100 last:border-0">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $school->name }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $school->code }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $school->currentAcademicYear?->name ?? 'Not set' }}</td>
                        <td class="px-4 py-3 text-right">
                            <x-form.button type="button" variant="secondary" wire:click="enter({{ $school->id }})">Open</x-form.button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-slate-500">No schools yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
