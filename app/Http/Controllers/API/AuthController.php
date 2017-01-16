<?php namespace App\Http\Controllers\API;

use Tymon\JWTAuth\JWTAuth;
use Illuminate\Http\Request;
use App\Services\AccountService;
use App\Transformers\BaseTransformer;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthController extends APIController
{

    /**
     * @type AccountService
     */
    private $account;

    /**
     * @type  JWTAuth
     */
    private $JWTAuth;

    /**
     * AuthController constructor.
     * @param AccountService $account
     */
    public function __construct(AccountService $account)
    {
        $this->account = $account;
        $this->JWTAuth = app('tymon.jwt.auth');
    }

    /**
     * Refresh an expired JWT token.
     * @return mixed
     * @throws UnauthorizedHttpException | AccessDeniedHttpException
     */
    public function refresh()
    {
        $JWTToken = $this->JWTAuth->getToken();
        if (! $JWTToken) {
            throw new UnauthorizedHttpException("token_absent");
        }

        try {
            $JWTToken = $this->JWTAuth->refresh($JWTToken);
        } catch (TokenInvalidException $e) {
            throw new AccessDeniedHttpException('token_invalid');
        }

        return $this->arrayResponse(['token' => $JWTToken]);
    }

    /**
     * Login a user by Facebook, create an account if first-time, and return his JWT access token.
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function login(Request $request)
    {
        $facebookAuthToken = $request->get('token');
        $user = $this->account->loginUserByFacebookAccessToken($facebookAuthToken);
        $JWTToken = $this->JWTAuth->fromUser($user);

        return $this->arrayResponse(['token' => $JWTToken]);
    }
    
    /** @return BaseTransformer */
    protected function transformer()
    {
        return null;
    }
}
