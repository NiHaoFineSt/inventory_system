<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        $allowed = array_map(fn (string $role) => UserRole::from($role), $roles);

        if (! $user || ! in_array($user->role, $allowed, true)) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
