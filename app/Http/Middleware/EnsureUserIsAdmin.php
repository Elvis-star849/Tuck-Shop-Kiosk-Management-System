<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            AuditLog::record(
                'access.denied',
                ($request->user()?->name ?? 'Guest').' attempted to access '.$request->path().' — ACCESS DENIED',
            );
            abort(403, 'You are not authorized to access this page.');
        }

        return $next($request);
    }
}
