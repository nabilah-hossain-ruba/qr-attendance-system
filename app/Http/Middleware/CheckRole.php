<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if ($role === 'student' && !$request->user()->isStudent()) {
            abort(403, 'Access denied. Student role required.');
        }
        
        if ($role === 'lecturer' && !$request->user()->isLecturer()) {
            abort(403, 'Access denied. Lecturer role required.');
        }
        
        if ($role === 'admin' && !$request->user()->isAdmin()) {
            abort(403, 'Access denied. Admin role required.');
        }
        
        return $next($request);
    }
}