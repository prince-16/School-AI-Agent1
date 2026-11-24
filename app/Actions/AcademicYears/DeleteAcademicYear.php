<?php

namespace App\Actions\AcademicYears;

use App\Exceptions\SchoolRuleException;
use App\Models\AcademicYear;
use App\Services\SchoolContext;

class DeleteAcademicYear
{
    public function handle(AcademicYear $year): void
    {
        $school = $year->school()->firstOrFail();

        app(SchoolContext::class)->forSchool($school, function () use ($school, $year): void {
            if ($school->current_academic_year_id === $year->id) {
                throw new SchoolRuleException('Set another academic year as current before deleting this one.');
            }

            $year->delete();
        });
    }
}
