<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;

class SessionTimeout
{
    protected $session;
    protected $timeout = 1800; // 30 minutes in seconds

    public function __construct(Store $session)
    {
        $this->session = $session;
    }

    public function handle($request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $lastActivity = $this->session->get('last_activity');

        if ($lastActivity && time() - $lastActivity > $this->timeout) {
            Auth::logout();
            $this->session->flush();
            return redirect()->route('login')->with('message', 'Your session has expired due to inactivity.');
        }

        $this->session->put('last_activity', time());

        return $next($request);
    }
}
