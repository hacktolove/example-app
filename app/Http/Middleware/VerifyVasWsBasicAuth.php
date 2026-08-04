<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyVasWsBasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $username = config('app.vas_ws.username');
        $password = config('app.vas_ws.password');

        $validUsername = $username && hash_equals($username, (string) $request->getUser());
        $validPassword = $password && hash_equals($password, (string) $request->getPassword());

        if (! $validUsername || ! $validPassword) {
            return response()->json(['success' => false, 'msg' => 'unauthorized'], 401, [
                'WWW-Authenticate' => 'Basic realm="vasws"',
            ]);
        }

        return $next($request);
    }
}
