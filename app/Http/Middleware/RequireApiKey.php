<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared-secret check for the /api/bmw routes. Send the key as an
 * `X-Api-Key` header or a `key` query parameter (handy for iOS Shortcuts).
 */
class RequireApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('bmw.api_key');
        $given = (string) ($request->header('X-Api-Key') ?? $request->query('key', ''));

        if ($expected === '' || ! hash_equals($expected, $given)) {
            abort(401, 'Invalid or missing API key');
        }

        return $next($request);
    }
}
