<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    ]);

    $middleware->api(prepend: [
        \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
    ]);

    $middleware->redirectGuestsTo(function (Request $request) {
        if ($request->is('api/*')) {
            return null;
        }
        return '/login';
    });
})

    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request) {
            return $request->expectsJson() || $request->is('api/*');
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e) {
            return response()->json(['message' => 'Resource not found.'], 404);
        });

        $exceptions->render(function (AuthenticationException $e) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        });

        $exceptions->render(function (AuthorizationException $e) {
            return response()->json(['message' => 'Forbidden.'], 403);
        });

        $exceptions->render(function (ThrottleRequestsException $e) {
            return response()->json(['message' => 'Too many requests. Please try again later.'], 429);
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        });

        $exceptions->render(function (Throwable $e) {
            $message = config('app.debug') ? $e->getMessage() : 'Internal server error.';

            return response()->json(['message' => $message], 500);
        });
    })->create();
