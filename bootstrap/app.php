<?php

use App\Http\Middleware\CheckContentManagementAccess;
use App\Http\Middleware\CheckMaintenance;
use App\Http\Middleware\CheckMediaManagementAccess;
use App\Http\Middleware\CheckPortalAccess;
use App\Http\Middleware\CheckPortalInformationAccess;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureActive;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => CheckRole::class,
            'portal' => CheckPortalAccess::class,
            'portal.info' => CheckPortalInformationAccess::class,
            'content.manage' => CheckContentManagementAccess::class,
            'media.manage' => CheckMediaManagementAccess::class,
            'active' => EnsureActive::class,
            'maintenance' => CheckMaintenance::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(function () {
            $user = auth()->user();

            if (! $user) {
                return route('login');
            }

            return route($user->dashboardRouteName());
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $exception, Request $request) {
            if ($exception instanceof ValidationException || $exception instanceof AuthenticationException) {
                return null;
            }

            $status = match (true) {
                $exception instanceof AuthenticationException => 401,
                $exception instanceof AccessDeniedHttpException => 403,
                $exception instanceof NotFoundHttpException => 404,
                method_exists($exception, 'getStatusCode') => $exception->getStatusCode(),
                default => 500,
            };
            $message = match (true) {
                $exception instanceof AuthenticationException => 'Vous devez être connecté pour accéder à cette page.',
                $exception instanceof AccessDeniedHttpException => 'Vous n’êtes pas autorisé à effectuer cette action.',
                $exception instanceof NotFoundHttpException => 'La page demandée est introuvable.',
                default => 'Une erreur inattendue est survenue. Merci de réessayer plus tard.',
            };

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => $message], $status === 0 ? 500 : $status);
            }

            if ($status === 403) {
                $portal = match (true) {
                    str_contains((string) ($request->path() ?? ''), 'portail-ecodim') || str_contains((string) ($request->route()?->getName() ?? ''), 'ecodim') => 'ecodim',
                    str_contains((string) ($request->path() ?? ''), 'portail-jeunesse') || str_contains((string) ($request->route()?->getName() ?? ''), 'youth') => 'youth',
                    default => 'church',
                };

                $contextMessage = match ($portal) {
                    'ecodim' => 'Cette section est réservée aux responsables autorisés d’ECODIM.',
                    'youth' => 'Cette section est réservée aux responsables autorisés de la Jeunesse.',
                    default => 'Cette section est réservée aux utilisateurs disposant des autorisations requises.',
                };

                $returnUrl = $request->user()?->dashboardRouteName()
                    ? route($request->user()->dashboardRouteName())
                    : route('home');

                return response()->view('errors.403', [
                    'portal' => $portal,
                    'contextMessage' => $contextMessage,
                    'returnUrl' => $returnUrl,
                ], 403);
            }

            return response()->view('errors.app', [
                'message' => $message,
            ], $status === 0 ? 500 : $status);
        });
    })->create();
