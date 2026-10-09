<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckFeature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $feature): Response
    {
        if (Auth::check() && Auth::user()->isDeveloper()) {
            return $next($request); // Developer bypasses feature checks
        }

        if (Auth::check() && Auth::user()->company && Auth::user()->company->hasFeature($feature)) {
            return $next($request);
        }

        return redirect()->route('dashboard')->with('error', 'Akses ditolak. Modul ini belum diaktifkan untuk akun Anda.');
    }
}
