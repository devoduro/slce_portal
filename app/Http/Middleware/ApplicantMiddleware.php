<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApplicantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Mirrors StudentMiddleware's role gate, plus the first-login password-change
     * redirect StudentMiddleware's dashboard routes get from the separate 'first.login'
     * alias - folded in here rather than a second aliased group, since applicants only
     * ever operate inside this one prefixed route group.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || Auth::user()->role !== 'applicant') {
            return redirect()->route('applicant.login')->with('error', 'Access denied. Applicants only.');
        }

        if (Auth::user()->first_login && !$request->is('applicant/change-password*') && !$request->is('applicant/update-password*')) {
            return redirect()->route('applicant.change-password')
                ->with('warning', 'Please change your password before continuing.');
        }

        return $next($request);
    }
}
