<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">{{ $school->name }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $school->code }} · {{ $school->timezone }}</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <section class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">Current academic year</p>
            <p class="mt-2 text-lg font-semibold text-slate-900">{{ $currentYear?->name ?? 'Not set' }}</p>
        </section>
        <section class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">Academic years</p>
            <p class="mt-2 text-lg font-semibold text-slate-900">{{ $yearCount }}</p>
        </section>
        <section class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">Members</p>
            <p class="mt-2 text-lg font-semibold text-slate-900">{{ $memberCount }}</p>
        </section>
        <section class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">Students</p>
            <p class="mt-2 text-lg font-semibold text-slate-900">{{ $studentCount }}</p>
        </section>
        <section class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">Classes</p>
            <p class="mt-2 text-lg font-semibold text-slate-900">{{ $classCount }}</p>
        </section>
    </div>
</div>
