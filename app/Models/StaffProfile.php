<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\StaffProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['employee_code', 'designation'])]
class StaffProfile extends Model
{
    /** @use HasFactory<StaffProfileFactory> */
    use BelongsToSchool, HasFactory;

    protected static function booted(): void
    {
        static::saving(function (StaffProfile $profile): void {
            $profile->employee_code = Str::upper(trim($profile->employee_code));
            $profile->designation = trim($profile->designation);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
