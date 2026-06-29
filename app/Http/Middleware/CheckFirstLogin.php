<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFirstLogin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && 
            auth()->user()->role === 'student' && 
            auth()->user()->first_login) {
            
            // If trying to access routes other than password change or dashboard
            if (!$request->is('password/change*') && 
                !$request->is('password/update*') && 
                !$request->is('student/dashboard')) {
                return redirect()->route('student.dashboard');
            }
            
            // Set session variable to show password change modal
            session(['show_password_change' => true]);
            
            // If on dashboard, let the request through to show the modal
            // If on password routes, let the request through to handle the change
            return $next($request);
        }

        return $next($request);
    }
}
