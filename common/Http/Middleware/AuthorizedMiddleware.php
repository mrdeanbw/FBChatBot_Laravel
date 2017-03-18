<?php namespace Common\Http\Middleware;

use Closure;
use Dingo\Api\Auth\Auth;
use Illuminate\Http\Request;
use Common\Services\UserService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthorizedMiddleware
{

    /**
     * Verify the user is authorized to use Mr. Reply.
     * @param Request $request
     * @param Closure $next
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     * @throws HttpException
     */
    public function handle(Request $request, Closure $next)
    {
        $user = app(Auth::class)->user();

        if (!app(UserService::class)->hasAllManagingPagePermissions($user->granted_permissions)) {
            throw new HttpException(403, "missing_permissions");
        }

        return $next($request);
    }
}