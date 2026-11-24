<?php

namespace App\Models\Concerns;

use App\Exceptions\SchoolRuleException;
use App\Models\School;
use App\Models\Scopes\SchoolScope;
use App\Services\SchoolContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToSchool
{
    public static function bootBelongsToSchool(): void
    {
        static::addGlobalScope(new SchoolScope);

        static::creating(function (Model $model): void {
            static::guardSchool($model);
        });

        static::updating(function (Model $model): void {
            static::guardSchool($model);
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    protected static function guardSchool(Model $model): void
    {
        $context = app(SchoolContext::class);

        if ($context->isBypassed()) {
            return;
        }

        $schoolId = $model->getAttribute('school_id');

        if ($context->id() === null) {
            if ($schoolId === null) {
                throw new SchoolRuleException('A school is required to save this record.');
            }

            return;
        }

        if ($schoolId === null) {
            $model->setAttribute('school_id', $context->id());
            $schoolId = $context->id();
        }

        if ((int) $schoolId !== $context->id()) {
            throw new SchoolRuleException('Cannot save a record for another school.');
        }
    }
}
