<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureJsonContentType
{
    /**
     * Tipos de contenido aceptados en peticiones de escritura.
     *
     * @var array<int, string>
     */
    private const ALLOWED = [
        'application/json',
        'application/x-www-form-urlencoded',
        'multipart/form-data',
    ];

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('post') && ! $request->isMethod('put') && ! $request->isMethod('patch')) {
            return $next($request);
        }

        $contentType = $request->header('Content-Type');

        if ($contentType === null || $contentType === '') {
            return $next($request);
        }

        $mime = strtolower(trim(explode(';', $contentType)[0]));

        if (! in_array($mime, self::ALLOWED, true) && ! str_ends_with($mime, '+json')) {
            return response()->json([
                'message' => 'El tipo de contenido no es soportado. Use application/json.',
                'error' => 'UNSUPPORTED_MEDIA_TYPE',
                'data' => null,
            ], 415);
        }

        return $next($request);
    }
}
