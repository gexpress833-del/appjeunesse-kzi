<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActive
{
    /**
     * Seuls les comptes validés (status = active) accèdent à l'application.
     * Les comptes 'pending' / 'inactive' sont déconnectés vers l'écran d'attente.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->status !== 'active') {
            if ($request->route()->getName() !== 'pending') {
                return redirect()->route('pending');
            }
        }

        return $next($request);
    }
}
