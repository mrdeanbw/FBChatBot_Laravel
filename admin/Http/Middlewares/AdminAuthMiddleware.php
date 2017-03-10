<?php namespace Admin\Http\Middlewares;

use Closure;
use Dingo\Api\Http\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AdminAuthMiddleware
{

    public function handle(Request $request, Closure $next)
    {
        if ($request->get('auth_token') != config('admin.auth_token')) {
            throw new UnauthorizedHttpException('Auth Token', 'Invalid Auth Token');
        }

        return $next($request);
    }
}