<?php

namespace App\Actions\Schools;

use App\Enums\SchoolPermission;
use App\Enums\SchoolRole;
use App\Models\School;
use App\Services\SchoolContext;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ProvisionSchoolAccess
{
    public function handle(School $school): void
    {
        foreach (SchoolPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        app(SchoolContext::class)->forSchool($school, function (): void {
            foreach (SchoolRole::cases() as $role) {
                $model = Role::findOrCreate($role->value, 'web');
                $model->syncPermissions(array_map(
                    fn (SchoolPermission $permission): string => $permission->value,
                    $role->permissions(),
                ));
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
