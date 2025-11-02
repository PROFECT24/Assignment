<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class FrontendAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Session::get('user');
        
        if (!$user || $user['role'] !== 'admin') {
            return redirect()->route('dashboard')->withErrors(['error' => 'Admin access required']);
        }

        return $next($request);
    }
}