<?php

namespace App\Actions\AcademicYears;

use App\Exceptions\SchoolRuleException;
use App\Models\AcademicYear;
use App\Models\School;
use App\Services\SchoolContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SaveAcademicYear
{
    /**
     * @param  array{name: string, starts_on: string, ends_on: string, make_current?: bool}  $attributes
     */
    public function handle(School $school, array $attributes, ?AcademicYear $year = null): AcademicYear
    {
        return app(SchoolContext::class)->forSchool($school, function () use ($school, $attributes, $year): AcademicYear {
            return DB::transaction(function () use ($school, $attributes, $year): AcademicYear {
                $startsOn = Carbon::parse($attributes['starts_on'])->startOfDay();
                $endsOn = Carbon::parse($attributes['ends_on'])->startOfDay();

                if ($endsOn->lessThanOrEqualTo($startsOn)) {
                    throw new SchoolRuleException('The end date must be after the start date.');
                }

                $overlaps = AcademicYear::query()
                    ->when($year, fn ($query) => $query->whereKeyNot($year->getKey()))
                    ->whereDate('starts_on', '<=', $endsOn->toDateString())
                    ->whereDate('ends_on', '>=', $startsOn->toDateString())
                    ->exists();

                if ($overlaps) {
                    throw new SchoolRuleException('This academic year overlaps an existing year.');
                }

                $year ??= new AcademicYear;
                $year->name = $attributes['name'];
                $year->starts_on = $startsOn->toDateString();
                $year->ends_on = $endsOn->toDateString();

                if (! $year->exists) {
                    $year->school_id = $school->id;
                }

                $year->save();

                if (($attributes['make_current'] ?? false) === true) {
                    $school->makeCurrent($year);
                }

                return $year->refresh();
            });
        });
    }
}
