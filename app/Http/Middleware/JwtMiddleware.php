<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class JwtMiddleware extends BaseMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    public function handle($request, Closure $next, $guard)
    {
        try {

            auth()->shouldUse($guard);

            JWTAuth::parseToken()->authenticate();

            return $next($request);

        } catch (Exception $e) {

            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token.'
                ], 401);

            } elseif ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {

                return response()->json([
                    'success' => false,
                    'message' => 'Expired token.'
                ], 401);

            } else {

                return response()->json([
                    'success' => false,
                    'message' => 'Token not found.'
                ], 401);

            }

        }
    }
}
