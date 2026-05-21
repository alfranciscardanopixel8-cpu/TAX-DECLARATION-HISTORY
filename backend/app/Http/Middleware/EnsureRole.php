<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user && $user->status === 'Active', 403, 'Your account is not active.');
        abort_unless(in_array($user->role, $roles, true), 403, 'Your account is not allowed to perform this action.');

        return $next($request);
    }
}
