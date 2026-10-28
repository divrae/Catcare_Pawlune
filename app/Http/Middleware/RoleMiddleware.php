<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            return redirect()->route('login')->withErrors(['identifier' => 'Akun kamu dinonaktifkan. Hubungi admin.']);
        }

        // Admin boleh masuk semua area internal, kecuali portal owner.
        $allowed = in_array($user->role, $roles, true)
            || ($user->role === 'admin' && ! in_array('owner', $roles, true));

        abort_unless($allowed, 403, 'Kamu tidak punya akses ke halaman ini.');

        return $next($request);
    }
}
