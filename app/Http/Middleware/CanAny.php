<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanAny
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        $permissionList = array_values(array_filter(array_map('trim', explode(',', $permissions))));

        if ($permissionList === []) {
            return abort(403, 'ليس لديك الصلاحيات المطلوبة لهذه العملية');
        }

        if ($user->canAny($permissionList)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Unauthorized. Missing any required permissions.',
            ], 403);
        }

        return abort(403, 'ليس لديك الصلاحيات المطلوبة لهذه العملية');
    }
}
