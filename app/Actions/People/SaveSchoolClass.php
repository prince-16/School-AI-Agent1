<?php

namespace App\Actions\People;

use App\Exceptions\SchoolRuleException;
use App\Models\School;
use App\Models\SchoolClass;
use App\Services\SchoolContext;

class SaveSchoolClass
{
    /**
     * @param  array{name: string, sort_order?: int}  $attributes
     */
    public function handle(School $school, array $attributes, ?SchoolClass $class = null): SchoolClass
    {
        return app(SchoolContext::class)->forSchool($school, function () use ($school, $attributes, $class): SchoolClass {
            $name = trim($attributes['name']);

            $duplicate = SchoolClass::query()
                ->where('name', $name)
                ->when($class, fn ($query) => $query->whereKeyNot($class->getKey()))
                ->exists();

            if ($duplicate) {
                throw new SchoolRuleException('This class already exists.');
            }

            $class ??= new SchoolClass;
            $class->name = $name;
            $class->sort_order = $attributes['sort_order'] ?? 0;

            if (! $class->exists) {
                $class->school_id = $school->id;
            }

            $class->save();

            return $class->refresh();
        });
    }
}
