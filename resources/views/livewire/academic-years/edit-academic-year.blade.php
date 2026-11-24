<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Edit academic year</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $academicYear->name }}</p>
    </div>

    <form wire:submit="save" class="space-y-4 rounded-lg border border-slate-200 bg-white p-4">
        <x-form.field name="name" label="Name" wire:model="name" />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.field name="starts_on" label="Start date" type="date" wire:model="starts_on" />
            <x-form.field name="ends_on" label="End date" type="date" wire:model="ends_on" />
        </div>
        <div class="flex flex-wrap gap-2">
            <x-form.button>Save changes</x-form.button>
            <a href="{{ route('academic-years.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-800">Cancel</a>
        </div>
    </form>
</div>
