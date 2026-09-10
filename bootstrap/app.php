<?php

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
            'active' => EnsureActive::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $exception, Request $request) {
            if ($exception instanceof ValidationException) {
                return null;
            }

            $status = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500;
            $message = match (true) {
                $exception instanceof AuthenticationException => 'Vous devez être connecté pour accéder à cette page.',
                $exception instanceof AccessDeniedHttpException => 'Vous n’êtes pas autorisé à effectuer cette action.',
                $exception instanceof NotFoundHttpException => 'La page demandée est introuvable.',
                default => 'Une erreur inattendue est survenue. Merci de réessayer plus tard.',
            };

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => $message], $status === 0 ? 500 : $status);
            }

            return response()->view('errors.app', [
                'message' => $message,
            ], $status === 0 ? 500 : $status);
        });
    })->create();
