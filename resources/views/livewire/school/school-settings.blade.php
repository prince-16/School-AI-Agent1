<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">School settings</h1>
        <p class="mt-1 text-sm text-slate-500">The school code is used to tell schools apart. It is stored in uppercase.</p>
    </div>

    @if ($status)
        <p class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ $status }}</p>
    @endif

    <form wire:submit="save" class="max-w-xl space-y-4 rounded-lg border border-slate-200 bg-white p-4">
        <x-form.field name="name" label="School name" wire:model="name" />
        <x-form.field name="code" label="School code" wire:model="code" />
        <x-form.field name="timezone" label="Timezone" wire:model="timezone" placeholder="Asia/Kolkata" />
        <x-form.button>Save settings</x-form.button>
    </form>
</div>
