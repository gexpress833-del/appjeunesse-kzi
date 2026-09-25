<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPortalAccess
{
    /**
     * Garantit qu'un utilisateur a bien accès au portail demandé.
     */
    public function handle(Request $request, Closure $next, string $portal): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Authentification requise.');
        }

        $requestedPortal = strtolower(trim($portal));

        if (! in_array($requestedPortal, ['church', 'youth', 'ecodim'], true) || ! $user->canAccessPortal($requestedPortal)) {
            abort(403, 'Ce compte n’a pas accès à ce portail.');
        }

        $request->attributes->set('portal', $requestedPortal);
        session()->put('active_portal', $requestedPortal);

        return $next($request);
    }
}
