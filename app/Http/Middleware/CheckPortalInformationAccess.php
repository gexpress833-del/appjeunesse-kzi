<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPortalInformationAccess
{
    public function handle(Request $request, Closure $next, string $portal): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Authentification requise.');
        }

        $requestedPortal = strtolower(trim($portal));

        if (! $user->canViewPortalInformation($requestedPortal)) {
            abort(403, 'Ce compte ne peut pas consulter ce portail.');
        }

        $request->attributes->set('portal', $requestedPortal);
        session()->put('active_portal', $requestedPortal);

        return $next($request);
    }
}
