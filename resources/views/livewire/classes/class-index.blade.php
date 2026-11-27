<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Classes</h1>
        <p class="mt-1 text-sm text-slate-500">A class stays with the school. Sections belong to one academic year.</p>
    </div>

    @if ($statusMessage)
        <p class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ $statusMessage }}</p>
    @endif

    @can('create', App\Models\SchoolClass::class)
        <form wire:submit="saveClass" class="space-y-4 rounded-lg border border-slate-200 bg-white p-4">
            <h2 class="text-sm font-semibold text-slate-900">Add a class</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.field name="class_name" label="Class name" wire:model="class_name" placeholder="10" />
                <x-form.field name="sort_order" label="Sort order" type="number" wire:model="sort_order" />
            </div>
            <x-form.button>Save class</x-form.button>
        </form>

        <form wire:submit="saveSection" class="space-y-4 rounded-lg border border-slate-200 bg-white p-4">
            <h2 class="text-sm font-semibold text-slate-900">Add a section</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="school_class_id" class="block text-sm font-medium text-slate-700">Class</label>
                    <select id="school_class_id" wire:model="school_class_id" class="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="">Choose a class</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                    @error('school_class_id')
                        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>
                <x-form.field name="section_name" label="Section name" wire:model="section_name" placeholder="A" />
                <div>
                    <label for="academic_year_id" class="block text-sm font-medium text-slate-700">Academic year</label>
                    <select id="academic_year_id" wire:model.live="academic_year_id" class="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                        @foreach ($years as $year)
                            <option value="{{ $year->id }}">{{ $year->name }}</option>
                        @endforeach
                    </select>
                    @error('academic_year_id')
                        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="class_teacher_user_id" class="block text-sm font-medium text-slate-700">Class teacher</label>
                    <select id="class_teacher_user_id" wire:model="class_teacher_user_id" class="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="">None</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                    @error('class_teacher_user_id')
                        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <x-form.button>Save section</x-form.button>
        </form>
    @else
        <div class="max-w-sm">
            <label for="academic_year_id" class="block text-sm font-medium text-slate-700">Academic year</label>
            <select id="academic_year_id" wire:model.live="academic_year_id" class="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                @foreach ($years as $year)
                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                @endforeach
            </select>
        </div>
    @endcan

    <div class="space-y-4">
        @forelse ($classes as $class)
            <section class="rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="text-sm font-semibold text-slate-900">{{ $class->name }}</h2>
                <ul class="mt-3 divide-y divide-slate-100 text-sm">
                    @forelse ($class->sections as $section)
                        <li class="flex justify-between gap-4 py-2">
                            <span>Section {{ $section->name }}</span>
                            <span class="text-slate-500">{{ $section->classTeacher?->name ?? 'No class teacher' }}</span>
                        </li>
                    @empty
                        <li class="py-2 text-slate-500">No sections for this academic year.</li>
                    @endforelse
                </ul>
            </section>
        @empty
            <p class="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-500">No classes yet.</p>
        @endforelse
    </div>
</div>
