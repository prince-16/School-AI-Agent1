<?php

namespace App\Models\Scopes;

use App\Services\SchoolContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SchoolScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(SchoolContext::class);

        if ($context->isBypassed()) {
            return;
        }

        $schoolId = $context->id();

        if ($schoolId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->qualifyColumn('school_id'), $schoolId);
    }
}
