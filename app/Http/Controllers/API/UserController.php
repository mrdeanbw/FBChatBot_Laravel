<?php namespace App\Http\Controllers\API;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Transformers\UserTransformer;
use App\Transformers\BaseTransformer;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class UserController extends APIController
{

    /**
     * @type AuthService
     */
    private $account;

    /**
     * @type  JWTAuth
     */
    private $JWTAuth;

    /**
     * AuthController constructor.
     * @param AuthService $account
     */
    public function __construct(AuthService $account)
    {
        $this->account = $account;
        $this->JWTAuth = app('tymon.jwt.auth');
    }

    /**
     * Refresh an expired JWT token.
     * @return mixed
     * @throws UnauthorizedHttpException | AccessDeniedHttpException
     */
    public function refreshToken()
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

        if (! $facebookAuthToken) {
            throw new BadRequestHttpException;
        }
        
        $user = $this->account->loginUserByFacebookAccessToken($facebookAuthToken);

        $JWTToken = $this->JWTAuth->fromUser($user);
        $user->jwt_token = $JWTToken;

        return $this->itemResponse($user);
    }

    /**
     * @return \Dingo\Api\Http\Response
     */
    public function current()
    {
        $user = $this->user();

        return $this->itemResponse($user);
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return new UserTransformer();
    }
}
