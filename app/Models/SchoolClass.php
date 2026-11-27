<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Models\Scopes\SchoolScope;
use Database\Factories\SchoolClassFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'sort_order'])]
class SchoolClass extends Model
{
    /** @use HasFactory<SchoolClassFactory> */
    use BelongsToSchool, HasFactory;

    protected static function booted(): void
    {
        static::saving(function (SchoolClass $class): void {
            $class->name = trim($class->name);
        });
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->withoutGlobalScope(SchoolScope::class);
    }
}
