<?php
namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\JWTAuth;

class JWTTokenMiddleware
{

    /** @type  JWTAuth */
    private $JWTAuth;

    public function __construct()
    {
        $this->JWTAuth = app('tymon.jwt.auth');
    }

    /**
     * @param         $request
     * @param Closure $next
     * @return mixed
     * @throws UnauthorizedHttpException | AccessDeniedHttpException
     */
    public function handle($request, Closure $next)
    {
        try {

            if (! $user = $this->JWTAuth->parseToken()->authenticate()) {
                throw new AccessDeniedHttpException("user_not_found");
            }

        } catch (TokenExpiredException $e) {

            throw new AccessDeniedHttpException("token_expired");

        } catch (TokenInvalidException $e) {

            throw new AccessDeniedHttpException("token_invalid");

        } catch (JWTException $e) {

            throw new UnauthorizedHttpException("token_absent");

        }

        return $next($request);
    }


}