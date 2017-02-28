<?php namespace App\Http\Controllers\API;

use App\Repositories\User\UserRepositoryInterface;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Transformers\UserTransformer;
use App\Transformers\BaseTransformer;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use App\Models\User;
use App\Services\ReferralService;

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
    private $refService;

    /**
     * AuthController constructor.
     *
     * @param AuthService             $account
     * @param UserRepositoryInterface $userRepo
     * @param ReferralService         $refService
     */
    public function __construct(AuthService $account, UserRepositoryInterface $userRepo, ReferralService $refService)
    {
        $this->account = $account;
        $this->JWTAuth = app('tymon.jwt.auth');
        $this->userRepository = $userRepo;
        $this->refService = $refService;
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

    public function getReferralCode(Request $request)
    {
        $user = !is_null($request->input('id')) ? User::find($request->input('id')) : 1;
        return $user->referral_code;
    }

    /*
     * Creates the connection between the parent and child.
     * The post parameter referralCode is encrypted!
     *
     * @param Request $request
     */

    public function makeReferral(Request $request)
    {
        if(!is_null($request->input('referralCode')) && !is_null($request->input('childId')))
        {
            $child = User::find($request->input('childId'));
            return $this->refService->createConnection($child, $request->input('referralCode'));
        }
    }
    /** @return BaseTransformer */
    protected function transformer()
    {
        return new UserTransformer();
    }
}
