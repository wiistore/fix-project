<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Cek user login + role sesuai. Pemakaian: ->middleware('role:admin') atau 'role:admin,kasir'.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! auth()->check()) {
            return redirect('/login');
        }

        $user = auth()->user();

        if ($user->status !== 'aktif') {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->with('error', 'Akun kamu sedang nonaktif.');
        }

        if (! empty($roles) && ! in_array($user->role, $roles, true)) {
            return redirect('/403');
        }

        return $next($request);
    }
}
