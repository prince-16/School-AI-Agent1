<div class="space-y-4">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Choose a school</h1>
        <p class="mt-1 text-sm text-slate-500">Records stay inside the school you open.</p>
    </div>

    @if ($schools->isEmpty())
        <p class="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-600">No schools are available for this account.</p>
    @else
        <ul class="divide-y divide-slate-200 overflow-hidden rounded-lg border border-slate-200 bg-white">
            @foreach ($schools as $school)
                <li class="flex items-center justify-between gap-4 px-4 py-3">
                    <div>
                        <p class="font-medium text-slate-900">{{ $school->name }}</p>
                        <p class="text-sm text-slate-500">{{ $school->code }}</p>
                    </div>
                    <x-form.button type="button" wire:click="select({{ $school->id }})">Open</x-form.button>
                </li>
            @endforeach
        </ul>
    @endif
</div>
