<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStarterSelected
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if not authenticated
        if (!auth()->check()) {
            return $next($request);
        }

        // Skip if already on starter selection page
        if ($request->routeIs('starter.*')) {
            return $next($request);
        }

        // Redirect to starter selection if user has no units
        if (auth()->user()->summonedUnits()->count() === 0) {
            return redirect()->route('starter.index')
                ->with('info', 'Please choose your starter unit to begin your journey!');
        }

        return $next($request);
    }
}
