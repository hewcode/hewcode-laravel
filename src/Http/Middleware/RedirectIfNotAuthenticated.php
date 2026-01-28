<?php

namespace Hewcode\Hewcode\Http\Middleware;

use Closure;
use Hewcode\Hewcode\Hewcode;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $panel): Response
    {
        $panelInstance = Hewcode::panel($panel);

        if (! auth()->check() && $panelInstance->isLoginEnabled()) {
            return redirect(Hewcode::route('login', panel: $panelInstance));
        }

        return $next($request);
    }
}
