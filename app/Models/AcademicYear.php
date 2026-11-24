<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\AcademicYearFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'starts_on', 'ends_on'])]
class AcademicYear extends Model
{
    /** @use HasFactory<AcademicYearFactory> */
    use BelongsToSchool, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }
}
