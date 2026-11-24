<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Academic years</h1>
        <p class="mt-1 text-sm text-slate-500">Fees, attendance, and exams will use the current year.</p>
    </div>

    @if ($error)
        <p class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $error }}</p>
    @endif

    @if ($status)
        <p class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ $status }}</p>
    @endif

    @can('create', App\Models\AcademicYear::class)
        <form wire:submit="save" class="space-y-4 rounded-lg border border-slate-200 bg-white p-4">
            <h2 class="text-sm font-semibold text-slate-900">Add an academic year</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-form.field name="name" label="Name" wire:model="name" placeholder="2026-27" />
                <x-form.field name="starts_on" label="Start date" type="date" wire:model="starts_on" />
                <x-form.field name="ends_on" label="End date" type="date" wire:model="ends_on" />
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model="make_current" class="rounded border-slate-300">
                Set as the current academic year
            </label>
            <x-form.button>Save academic year</x-form.button>
        </form>
    @endcan

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-slate-200 text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Name</th>
                    <th class="px-4 py-3 font-medium">Starts</th>
                    <th class="px-4 py-3 font-medium">Ends</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($years as $year)
                    <tr class="border-b border-slate-100 last:border-0">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $year->name }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $year->starts_on->toFormattedDateString() }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $year->ends_on->toFormattedDateString() }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $currentYearId === $year->id ? 'Current' : '' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap justify-end gap-2">
                                @can('update', $year)
                                    @if ($currentYearId !== $year->id)
                                        <x-form.button type="button" variant="secondary" wire:click="makeCurrent({{ $year->id }})">Make current</x-form.button>
                                    @endif
                                    <a href="{{ route('academic-years.edit', $year) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-800">Edit</a>
                                @endcan
                                @can('delete', $year)
                                    <x-form.button type="button" variant="secondary" wire:click="delete({{ $year->id }})" wire:confirm="Delete this academic year?">Delete</x-form.button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-slate-500">No academic years yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
