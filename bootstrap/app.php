<?php

use App\Http\Middleware\EnsureJsonContentType;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->throttleApi();

        $middleware->api(prepend: [
            EnsureJsonContentType::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $errorResponse = fn (string $message, string $code, int $status, array $extra = []) => response()->json(
            array_merge(['message' => $message, 'error' => $code, 'data' => null], $extra),
            $status,
        );

        $exceptions->render(function (ValidationException $e, Request $request) use ($errorResponse) {
            if ($request->is('api/*')) {
                return $errorResponse('Los datos enviados no son válidos.', 'VALIDATION_ERROR', 422, [
                    'errors' => $e->errors(),
                ]);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($errorResponse) {
            if ($request->is('api/*')) {
                $message = str_starts_with($request->path(), 'api/v1/products')
                    ? 'Producto no encontrado.'
                    : 'Recurso no encontrado.';

                return $errorResponse($message, 'NOT_FOUND', 404);
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) use ($errorResponse) {
            if ($request->is('api/*')) {
                return $errorResponse('Método no permitido para esta ruta.', 'METHOD_NOT_ALLOWED', 405)
                    ->withHeaders($e->getHeaders());
            }
        });

        $exceptions->render(function (BadRequestHttpException $e, Request $request) use ($errorResponse) {
            if ($request->is('api/*')) {
                return $errorResponse('Solicitud incorrecta.', 'BAD_REQUEST', 400)
                    ->withHeaders($e->getHeaders());
            }
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($errorResponse) {
            if ($request->is('api/*')) {
                return $errorResponse('Demasiadas peticiones.', 'TOO_MANY_REQUESTS', 429)
                    ->withHeaders($e->getHeaders());
            }
        });

        $exceptions->render(function (Throwable $e, Request $request) use ($errorResponse) {
            if (! $request->is('api/*') || $e instanceof HttpResponseException || $e instanceof HttpExceptionInterface) {
                return null;
            }

            return $errorResponse('Error interno del servidor.', 'SERVER_ERROR', 500);
        });
    })->create();
