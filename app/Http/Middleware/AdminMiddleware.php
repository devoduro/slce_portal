<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please log in to access this area.');
        }

        $user = Auth::user();
        
        // Check if user has admin role
        if ($user->role !== User::ROLE_ADMIN) {
            // Log unauthorized access attempt
            \Illuminate\Support\Facades\Log::warning('Unauthorized admin access attempt', [
                'user_id' => $user->id,
                'role' => $user->role,
                'ip' => $request->ip(),
                'url' => $request->fullUrl()
            ]);
            
            // If student tries to access admin area, redirect to student dashboard
            if ($user->role === User::ROLE_STUDENT) {
                return redirect()->route('student.dashboard')
                    ->with('error', 'Access denied. This area is restricted to administrators only.');
            }
            
            // For any other role, redirect to login
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('login')
                ->with('error', 'Access denied. This area is restricted to administrators only.');
        }

        return $next($request);
    }
}
