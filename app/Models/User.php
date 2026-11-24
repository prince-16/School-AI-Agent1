<?php

namespace App\Models;

use App\Enums\SchoolPermission;
use App\Models\Scopes\SchoolScope;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_admin' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            $user->email = Str::lower($user->email);
        });
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(SchoolMembership::class)->withoutGlobalScope(SchoolScope::class);
    }

    public function canEnterSchool(School $school): bool
    {
        if ($this->is_platform_admin) {
            return true;
        }

        return $this->memberships()->active()->where('school_id', $school->id)->exists();
    }

    public function hasSchoolPermission(SchoolPermission $permission): bool
    {
        if ($this->is_platform_admin) {
            return true;
        }

        return $this->hasPermissionTo($permission->value);
    }
}
