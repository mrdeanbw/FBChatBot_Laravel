<?php

namespace App\Http\Controllers\API;

use App\Services\AccountService;
use App\Services\Facebook\AuthService;
use App\Transformers\BaseTransformer;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\JWTAuth;

class AuthController extends APIController
{

    /**
     * @type AuthService
     */
    private $facebookAuth;

    /**
     * @type AccountService
     */
    private $account;
    /** @type  JWTAuth */
    private $JWTAuth;

    /**
     * AuthController constructor.
     * @param AuthService    $FacebookAuth
     * @param AccountService $account
     */
    public function __construct(AuthService $FacebookAuth, AccountService $account)
    {
        $this->facebookAuth = $FacebookAuth;
        $this->account = $account;
        $this->JWTAuth = app('tymon.jwt.auth');
    }

    /**
     * @return mixed
     * @throws UnauthorizedHttpException | AccessDeniedHttpException
     */
    public function index()
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

        return $this->response->array(['data' => ['token' => $JWTToken]]);

    }

    /**
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function store(Request $request)
    {
        $facebookAuthToken = $request->get('token');

        $clientId = config('services.facebook.client_id');
        $clientSecret = config('services.facebook.client_secret');

        try {
            $facebookUser = $this->facebookAuth->getUser($facebookAuthToken);
            $accessToken = $this->facebookAuth->getExtendedAccessToken($facebookAuthToken, $clientId, $clientSecret)['access_token'];
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        
        $user = $this->persistUser($facebookUser, $accessToken);
        $JWTToken = $this->JWTAuth->fromUser($user);

        return $this->response->array(['data' => ['token' => $JWTToken]]);
    }

    /**
     * @param array  $facebookUser
     * @param string $longLivedAccessToken
     * @return \App\Models\User
     */
    protected function persistUser($facebookUser, $longLivedAccessToken)
    {
        $facebookUser['facebook_id'] = $facebookUser['id'];
        $facebookUser['access_token'] = $longLivedAccessToken;
        $facebookUser['granted_permissions'] = $this->facebookAuth->getGrantedPermissionList($longLivedAccessToken);
        unset($facebookUser['id']);

        return $this->account->createOrGetUser($facebookUser);
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return null;
    }
}
