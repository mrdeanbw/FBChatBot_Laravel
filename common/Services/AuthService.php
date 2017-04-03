<?php namespace Common\Services;

use Common\Models\User;
use GuzzleHttp\Exception\ClientException;
use Common\Services\Facebook\Auth as FacebookAuthService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AuthService
{

    /**
     * @type FacebookAuthService
     */
    private $FacebookAuth;

    /**
     * @type UserService
     */
    private $users;

    /**
     * AccountService constructor.
     * @param UserService         $users
     * @param FacebookAuthService $FacebookAuth
     */
    public function __construct(UserService $users, FacebookAuthService $FacebookAuth)
    {
        $this->users = $users;
        $this->FacebookAuth = $FacebookAuth;
    }

    /**
     * Login a user by his Facebook access token.
     * @param $facebookAuthToken
     * @return User
     */
    public function loginUserByFacebookAccessToken($facebookAuthToken)
    {
        /**
         * Retrieve the user public profile and extended access token through Facebook API.
         */
        try {
            $user = $this->FacebookAuth->getUser($facebookAuthToken);
            $user['access_token'] = $this->getExtendedFacebookAccessToken($facebookAuthToken);
        } catch (ClientException $e) {
            $body = json_decode($e->getResponse()->getBody());
            if ($body && isset($body->error->code) && $body->error->code == 190) {
                throw new BadRequestHttpException("Invalid Facebook access token.");
            }
            throw $e;
        }

        /**
         * Grab the list of permissions the user has given to our app.
         */
        $user['granted_permissions'] = $this->getGrantedFacebookPermissions($user['access_token']);

        /**
         * Login/register the user.
         */
        return $this->users->createOrUpdateUser($user);
    }

    /**
     * @return array
     */
    private function getFacebookAppCredentials()
    {
        $clientId = config('services.facebook.client_id');
        $clientSecret = config('services.facebook.client_secret');

        return [$clientId, $clientSecret];
    }

    /**
     * @param string $facebookAuthToken
     * @return string
     */
    private function getExtendedFacebookAccessToken($facebookAuthToken)
    {
        list($clientId, $clientSecret) = $this->getFacebookAppCredentials();

        $extendedAccessToken = $this->FacebookAuth->getExtendedAccessToken(
            $facebookAuthToken,
            $clientId,
            $clientSecret
        )['access_token'];

        return $extendedAccessToken;
    }

    /**
     * @param $extendedAccessToken
     * @return array
     */
    private function getGrantedFacebookPermissions($extendedAccessToken)
    {
        $grantedPermissions = $this->FacebookAuth->getGrantedPermissionList($extendedAccessToken);

        return $grantedPermissions;
    }

}