<?php

namespace App\Http\Middleware;

use App\Services\SchoolContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentSchool
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app(SchoolContext::class)->id() !== null) {
            return $next($request);
        }

        $user = $request->user();

        if ($user?->is_platform_admin && ! $user->memberships()->active()->exists()) {
            return redirect()->route('platform.schools.index');
        }

        if ($user?->memberships()->active()->exists()) {
            return redirect()->route('schools.select');
        }

        return redirect()->route('schools.none');
    }
}
