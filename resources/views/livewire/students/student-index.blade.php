<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Students</h1>
        <p class="mt-1 text-sm text-slate-500">Admission numbers belong to this school. Enroll a student into a section after saving the record.</p>
    </div>

    @can('create', App\Models\Student::class)
        <form wire:submit="save" class="space-y-4 rounded-lg border border-slate-200 bg-white p-4">
            <h2 class="text-sm font-semibold text-slate-900">Add a student</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.field name="admission_number" label="Admission number" wire:model="admission_number" />
                <x-form.field name="first_name" label="First name" wire:model="first_name" />
                <x-form.field name="last_name" label="Last name" wire:model="last_name" />
                <x-form.field name="date_of_birth" label="Date of birth" type="date" wire:model="date_of_birth" />
                <div>
                    <label for="gender" class="block text-sm font-medium text-slate-700">Gender</label>
                    <select id="gender" wire:model="gender" class="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="">Not specified</option>
                        @foreach ($genders as $genderOption)
                            <option value="{{ $genderOption->value }}">{{ $genderOption->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <x-form.button>Save student</x-form.button>
        </form>
    @endcan

    <div>
        <label for="search" class="block text-sm font-medium text-slate-700">Search</label>
        <input id="search" type="search" wire:model.live="search" placeholder="Name or admission number" class="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm sm:max-w-sm">
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-slate-200 text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Admission number</th>
                    <th class="px-4 py-3 font-medium">Name</th>
                    <th class="px-4 py-3 font-medium">Section</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $student)
                    @php $enrollment = $student->enrollments->first(); @endphp
                    <tr class="border-b border-slate-100 last:border-0">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $student->admission_number }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $student->fullName() }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $enrollment?->section?->label() ?? 'Not enrolled' }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $student->status->label() }}</td>
                        <td class="px-4 py-3 text-right">
                            @can('update', $student)
                                <a href="{{ route('students.edit', $student) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-800">Edit</a>
                            @else
                                @can('view', $student)
                                    <a href="{{ route('students.edit', $student) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-800">View</a>
                                @endcan
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-slate-500">No students yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
