@component('layouts.guest')
    <div class="rounded-lg border border-slate-200 bg-white p-6">
        <h1 class="text-xl font-semibold text-slate-900">Log in</h1>
        <p class="mt-1 text-sm text-slate-500">Use the account your school administrator created.</p>

        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
            @csrf
            <x-form.field name="email" label="Email" type="email" value="{{ old('email') }}" required autofocus />
            <x-form.field name="password" label="Password" type="password" required />
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300" @checked(old('remember'))>
                Remember me
            </label>
            <x-form.button>Log in</x-form.button>
        </form>
    </div>
@endcomponent
