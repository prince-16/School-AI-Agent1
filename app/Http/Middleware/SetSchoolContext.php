<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Services\SchoolContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetSchoolContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(SchoolContext::class);
        $context->forget();

        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $schoolId = $request->session()->get('current_school_id');
        $school = is_numeric($schoolId) ? School::query()->find((int) $schoolId) : null;

        if (! $school instanceof School || ! $user->canEnterSchool($school)) {
            $request->session()->forget('current_school_id');
            $school = null;

            $membershipSchoolIds = $user->memberships()->active()->pluck('school_id');

            if ($membershipSchoolIds->count() === 1) {
                $school = School::query()->find($membershipSchoolIds->first());
            }
        }

        if ($school instanceof School) {
            $context->set($school);
            $request->session()->put('current_school_id', $school->id);
        }

        return $next($request);
    }
}
