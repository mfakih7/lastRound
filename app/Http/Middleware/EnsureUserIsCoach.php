<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsCoach
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || (! $user->isCoach() && ! $user->isAdmin())) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have access to the coach area.');
        }

        return $next($request);
    }
}
