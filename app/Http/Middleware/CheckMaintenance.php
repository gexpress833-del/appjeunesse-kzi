<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (AppSetting::current()->maintenance_mode && ! $request->user()?->isAdmin()) {
            return response()->view('errors.maintenance', status: 503);
        }

        return $next($request);
    }
}
