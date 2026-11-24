<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Members</h1>
        <p class="mt-1 text-sm text-slate-500">Roles apply only inside this school. An existing email is added without changing that person's password.</p>
    </div>

    @if ($error)
        <p class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $error }}</p>
    @endif

    @if ($status)
        <p class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ $status }}</p>
    @endif

    @can('create', App\Models\SchoolMembership::class)
        <form wire:submit="add" class="space-y-4 rounded-lg border border-slate-200 bg-white p-4">
            <h2 class="text-sm font-semibold text-slate-900">Add a member</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.field name="name" label="Name" wire:model="name" />
                <x-form.field name="email" label="Email" type="email" wire:model="email" />
                <div>
                    <label for="role" class="block text-sm font-medium text-slate-700">Role</label>
                    <select id="role" wire:model="role" class="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                        @foreach ($roles as $roleOption)
                            <option value="{{ $roleOption->value }}">{{ $roleOption->label() }}</option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>
                <x-form.field name="password" label="Password" type="password" wire:model="password" />
                <x-form.field name="password_confirmation" label="Confirm password" type="password" wire:model="password_confirmation" />
            </div>
            <x-form.button>Add member</x-form.button>
        </form>
    @endcan

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-slate-200 text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Name</th>
                    <th class="px-4 py-3 font-medium">Email</th>
                    <th class="px-4 py-3 font-medium">Role</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($memberships as $membership)
                    <tr class="border-b border-slate-100 last:border-0">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $membership->user->name }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $membership->user->email }}</td>
                        <td class="px-4 py-3">
                            @php
                                $roleName = $membership->user->roles->first()?->name;
                                $role = $roleName ? \App\Enums\SchoolRole::tryFrom($roleName) : null;
                            @endphp
                            @can('update', $membership)
                                <select wire:change="changeRole({{ $membership->id }}, $event.target.value)" class="rounded-md border border-slate-300 bg-white px-2 py-1 text-sm">
                                    @foreach ($roles as $roleOption)
                                        <option value="{{ $roleOption->value }}" @selected($roleName === $roleOption->value)>{{ $roleOption->label() }}</option>
                                    @endforeach
                                </select>
                            @else
                                {{ $role?->label() ?? 'Member' }}
                            @endcan
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $membership->status->value === 'active' ? 'Active' : 'Suspended' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap justify-end gap-2">
                                @can('update', $membership)
                                    @if ($membership->status->value === 'active')
                                        <x-form.button type="button" variant="secondary" wire:click="suspend({{ $membership->id }})">Suspend</x-form.button>
                                    @else
                                        <x-form.button type="button" variant="secondary" wire:click="reactivate({{ $membership->id }})">Reactivate</x-form.button>
                                    @endif
                                @endcan
                                @can('delete', $membership)
                                    <x-form.button type="button" variant="secondary" wire:click="remove({{ $membership->id }})" wire:confirm="Remove this member from the school?">Remove</x-form.button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
