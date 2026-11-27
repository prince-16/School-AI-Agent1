<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name') }}</title>
        @fonts
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        @livewireStyles
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-5xl flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <a href="{{ route('dashboard') }}" class="text-sm font-semibold tracking-wide text-slate-900">{{ config('app.name') }}</a>
                    @if ($currentSchool)
                        <p class="text-sm text-slate-500">{{ $currentSchool->name }}</p>
                    @endif
                </div>
                @auth
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                        <span class="text-slate-600">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-slate-700 underline-offset-4 hover:underline">Log out</button>
                        </form>
                    </div>
                @endauth
            </div>
            @auth
                <nav class="mx-auto flex max-w-5xl flex-wrap gap-2 px-4 pb-4 text-sm">
                    @if ($currentSchool)
                        <a href="{{ route('dashboard') }}" class="rounded-md px-3 py-1.5 text-slate-700 hover:bg-slate-100">Dashboard</a>
                        @can('viewAny', App\Models\AcademicYear::class)
                            <a href="{{ route('academic-years.index') }}" class="rounded-md px-3 py-1.5 text-slate-700 hover:bg-slate-100">Academic years</a>
                        @endcan
                        @can('viewAny', App\Models\Student::class)
                            <a href="{{ route('students.index') }}" class="rounded-md px-3 py-1.5 text-slate-700 hover:bg-slate-100">Students</a>
                        @endcan
                        @can('viewAny', App\Models\SchoolClass::class)
                            <a href="{{ route('classes.index') }}" class="rounded-md px-3 py-1.5 text-slate-700 hover:bg-slate-100">Classes</a>
                        @endcan
                        @can('viewAny', App\Models\StaffProfile::class)
                            <a href="{{ route('staff.index') }}" class="rounded-md px-3 py-1.5 text-slate-700 hover:bg-slate-100">Staff</a>
                        @endcan
                        @can('viewAny', App\Models\SchoolMembership::class)
                            <a href="{{ route('members.index') }}" class="rounded-md px-3 py-1.5 text-slate-700 hover:bg-slate-100">Members</a>
                        @endcan
                        @can('update', $currentSchool)
                            <a href="{{ route('school.settings') }}" class="rounded-md px-3 py-1.5 text-slate-700 hover:bg-slate-100">Settings</a>
                        @endcan
                    @endif
                    @if ($canSwitchSchool)
                        <a href="{{ route('schools.select') }}" class="rounded-md px-3 py-1.5 text-slate-700 hover:bg-slate-100">Switch school</a>
                    @endif
                    @if (auth()->user()->is_platform_admin)
                        <a href="{{ route('platform.schools.index') }}" class="rounded-md px-3 py-1.5 text-slate-700 hover:bg-slate-100">All schools</a>
                    @endif
                </nav>
            @endauth
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8">
            @if (session('status'))
                <p class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
            @endif
            {{ $slot }}
        </main>
        @livewireScripts
    </body>
</html>
