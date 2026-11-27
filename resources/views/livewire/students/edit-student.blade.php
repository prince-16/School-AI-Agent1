<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">{{ $student->fullName() }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $student->admission_number }}</p>
    </div>

    @if ($statusMessage)
        <p class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ $statusMessage }}</p>
    @endif

    @can('update', $student)
    <form wire:submit="save" class="space-y-4 rounded-lg border border-slate-200 bg-white p-4">
        <h2 class="text-sm font-semibold text-slate-900">Student</h2>
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
            <div>
                <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                <select id="status" wire:model="status" class="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                    @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <x-form.button>Save student</x-form.button>
    </form>
    @endcan

    <section class="space-y-4 rounded-lg border border-slate-200 bg-white p-4">
        <h2 class="text-sm font-semibold text-slate-900">Parents and guardians</h2>
        <ul class="divide-y divide-slate-100 text-sm">
            @forelse ($guardians as $link)
                <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                    <span class="font-medium text-slate-900">{{ $link->guardian->name }}</span>
                    <span class="text-slate-500">{{ $link->relationship->label() }}{{ $link->is_primary ? ' · Primary' : '' }}{{ $link->guardian->phone ? ' · '.$link->guardian->phone : '' }}</span>
                </li>
            @empty
                <li class="py-2 text-slate-500">No guardian linked yet.</li>
            @endforelse
        </ul>
        @can('update', $student)
        <form wire:submit="addGuardian" class="grid gap-4 sm:grid-cols-2">
            <x-form.field name="guardian_name" label="Name" wire:model="guardian_name" />
            <div>
                <label for="guardian_relationship" class="block text-sm font-medium text-slate-700">Relationship</label>
                <select id="guardian_relationship" wire:model="guardian_relationship" class="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                    @foreach ($relationships as $relationship)
                        <option value="{{ $relationship->value }}">{{ $relationship->label() }}</option>
                    @endforeach
                </select>
                @error('guardian_relationship')
                    <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>
            <x-form.field name="guardian_phone" label="Phone" wire:model="guardian_phone" />
            <x-form.field name="guardian_email" label="Email" type="email" wire:model="guardian_email" />
            <label class="flex items-center gap-2 text-sm text-slate-700 sm:col-span-2">
                <input type="checkbox" wire:model="guardian_is_primary" class="rounded border-slate-300">
                Primary contact
            </label>
            <div>
                <x-form.button>Add guardian</x-form.button>
            </div>
        </form>
        @endcan
    </section>

    <section class="space-y-4 rounded-lg border border-slate-200 bg-white p-4">
        <h2 class="text-sm font-semibold text-slate-900">Enrollment</h2>
        <p class="text-sm text-slate-500">Current academic year: {{ $currentYearName ?? 'Not set' }}</p>
        <ul class="divide-y divide-slate-100 text-sm">
            @forelse ($enrollments as $enrollment)
                <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                    <span>{{ $enrollment->academicYear->name }} · {{ $enrollment->section->label() }}{{ $enrollment->roll_number ? ' · Roll '.$enrollment->roll_number : '' }}</span>
                    <span class="flex items-center gap-2">
                        <span class="text-slate-500">{{ $enrollment->status->label() }}</span>
                        @if ($enrollment->status->value === 'enrolled')
                            @can('update', $enrollment)
                                <x-form.button type="button" variant="secondary" wire:click="withdraw({{ $enrollment->id }})">Withdraw</x-form.button>
                            @endcan
                        @endif
                    </span>
                </li>
            @empty
                <li class="py-2 text-slate-500">Not enrolled yet.</li>
            @endforelse
        </ul>
        @if ($currentYearName)
            @can('create', App\Models\Enrollment::class)
            <form wire:submit="enroll" class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="section_id" class="block text-sm font-medium text-slate-700">Section</label>
                    <select id="section_id" wire:model="section_id" class="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="">Choose a section</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}">{{ $section->label() }}</option>
                        @endforeach
                    </select>
                    @error('section_id')
                        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>
                <x-form.field name="roll_number" label="Roll number" wire:model="roll_number" />
                <div>
                    <x-form.button>Enroll</x-form.button>
                </div>
            </form>
            @endcan
        @endif
    </section>

    <a href="{{ route('students.index') }}" class="inline-block text-sm text-slate-700 underline-offset-4 hover:underline">Back to students</a>
</div>
