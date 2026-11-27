<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Models\Scopes\SchoolScope;
use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use BelongsToSchool, HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Section $section): void {
            $section->name = trim($section->name);
        });
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class)->withoutGlobalScope(SchoolScope::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class)->withoutGlobalScope(SchoolScope::class);
    }

    public function classTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'class_teacher_user_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class)->withoutGlobalScope(SchoolScope::class);
    }

    public function label(): string
    {
        return trim($this->schoolClass->name.' '.$this->name);
    }
}
