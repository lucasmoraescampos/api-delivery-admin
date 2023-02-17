<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;

class CheckUserAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::user()->type == User::TYPE_ADMIN) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized user'
        ], 401);
    }
}
