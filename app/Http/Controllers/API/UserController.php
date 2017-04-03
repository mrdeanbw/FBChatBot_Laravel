<?php namespace App\Http\Controllers\API;

use Tymon\JWTAuth\JWTAuth;
use Illuminate\Http\Request;
use Common\Services\AuthService;
use Common\Transformers\UserTransformer;
use Common\Transformers\BaseTransformer;
use Common\Repositories\User\UserRepositoryInterface;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
    private $userRepository;

    /**
     * AuthController constructor.
     *
     * @param AuthService             $account
     * @param UserRepositoryInterface $userRepo
     */
    public function __construct(AuthService $account, UserRepositoryInterface $userRepo)
    {
        $this->account = $account;
        $this->JWTAuth = app('tymon.jwt.auth');
        $this->userRepository = $userRepo;
        parent::__construct();
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
            throw new BadRequestHttpException("Facebook Access token is missing.");
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
