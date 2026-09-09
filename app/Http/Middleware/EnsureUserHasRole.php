<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Vérifie que l'utilisateur a au moins le rôle requis (hiérarchie : user < technician < admin).
     * Usage : ->middleware('role:technician')
     */
    public function handle(Request $request, Closure $next, string $minimumRole): Response
    {
        $user = $request->user();

        abort_unless(
            $user && $user->role->isAtLeast(UserRole::from($minimumRole)),
            403
        );

        return $next($request);
    }
}
