<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        abort_unless($user->is_active, 403, 'Votre compte a ete desactive.');
        abort_unless($user->isAdmin(), 403, 'Acces reserve aux administrateurs.');

        return $next($request);
    }
}
