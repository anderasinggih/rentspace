<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTvToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('tv.token', '');
        $given = (string) $request->route('token');

        if ($expected === '' || $given === '' || ! hash_equals($expected, $given)) {
            abort(404);
        }

        return $next($request);
    }
}