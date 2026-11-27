<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Models\Concerns\BelongsToSchool;
use App\Models\Scopes\SchoolScope;
use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['roll_number', 'status'])]
class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use BelongsToSchool, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Enrollment $enrollment): void {
            $enrollment->roll_number = filled($enrollment->roll_number) ? trim($enrollment->roll_number) : null;
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class)->withoutGlobalScope(SchoolScope::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class)->withoutGlobalScope(SchoolScope::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class)->withoutGlobalScope(SchoolScope::class);
    }
}
