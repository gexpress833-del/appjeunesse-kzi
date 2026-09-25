<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMediaManagementAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->managesMedia()) {
            abort(403, 'La gestion des médias est réservée au DCC autorisé.');
        }

        return $next($request);
    }
}
