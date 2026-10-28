<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Staff baru (password sementara dari admin) wajib ganti password dulu. */
class CheckPasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password
            && ! $request->routeIs('password.change', 'password.change.update', 'logout')) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
