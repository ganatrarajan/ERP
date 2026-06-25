<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebErpContextMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Bypass for mobile endpoints
        if ($request->is('api/mobile/*') || $request->is('mobile/*')) {
            return $next($request);
        }

        $user = $request->user();
        if ($user) {
            // Resolve selection and register in runtime context
            $academicYearId = \App\Support\AcademicYearContext::getWebAcademicYearId();
            if ($academicYearId) {
                \App\Support\AcademicYearContext::setWebAcademicYearId($academicYearId);
            }
        }

        return $next($request);
    }
}
