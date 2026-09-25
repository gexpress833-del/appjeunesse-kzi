<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckContentManagementAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->manageableContentSourcesForCurrentPortal() === []) {
            abort(403, 'Ce compte ne peut pas gérer les communications.');
        }

        return $next($request);
    }
}
