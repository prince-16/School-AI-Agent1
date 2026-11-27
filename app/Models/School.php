<?php

namespace App\Models;

use App\Exceptions\SchoolRuleException;
use App\Models\Scopes\SchoolScope;
use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'code', 'timezone'])]
class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (School $school): void {
            $school->code = Str::upper(trim($school->code));
        });
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(SchoolMembership::class)->withoutGlobalScope(SchoolScope::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class)->withoutGlobalScope(SchoolScope::class);
    }

    public function schoolClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class)->withoutGlobalScope(SchoolScope::class);
    }

    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class)
            ->withoutGlobalScope(SchoolScope::class)
            ->orderByDesc('starts_on')
            ->orderByDesc('id');
    }

    public function currentAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'current_academic_year_id')
            ->withoutGlobalScope(SchoolScope::class);
    }

    public function makeCurrent(AcademicYear $year): void
    {
        if ($year->school_id !== $this->id) {
            throw new SchoolRuleException('That academic year belongs to another school.');
        }

        $this->current_academic_year_id = $year->id;
        $this->save();
    }
}
