<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\GuardianFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['name', 'phone', 'email'])]
class Guardian extends Model
{
    /** @use HasFactory<GuardianFactory> */
    use BelongsToSchool, HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Guardian $guardian): void {
            $guardian->name = trim($guardian->name);
            $guardian->phone = filled($guardian->phone) ? trim($guardian->phone) : null;
            $guardian->email = filled($guardian->email) ? Str::lower(trim($guardian->email)) : null;
        });
    }
}
