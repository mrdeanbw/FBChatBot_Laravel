<?php namespace App\Services;

use App\Models\User;
use App\Services\Facebook\AuthService;
use App\Repositories\User\UserRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AccountService
{

    /**
     * @type AuthService
     */
    private $FacebookAuth;

    /**
     * @type UserRepository
     */
    private $userRepo;

    /**
     * AccountService constructor.
     * @param UserRepository $userRepo
     * @param AuthService    $FacebookAuth
     */
    public function __construct(UserRepository $userRepo, AuthService $FacebookAuth)
    {
        $this->userRepo = $userRepo;
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
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        /**
         * Grab the list of permissions the user has given to our app.
         */
        $user['granted_permissions'] = $this->getGrantedFacebookPermissions($user['access_token']);

        /**
         * Login/register the user.
         */
        return $this->createOrUpdateUser($user);
    }


    /**
     * Get user by Facebook ID if exists, and update his info
     * Or create a new User instance and return it.
     * @param $userData
     * @return User
     */
    public function createOrUpdateUser(array $userData)
    {
        $data = [
            'facebook_id'         => $userData['id'],
            'full_name'           => $userData['name'],
            'first_name'          => $userData['first_name'],
            'last_name'           => $userData['last_name'],
            'gender'              => $userData['gender'],
            'avatar_url'          => array_get($userData, 'picture.data.url'),
            'email'               => array_get($userData, 'email'),
            'access_token'        => $userData['access_token'],
            'granted_permissions' => $userData['granted_permissions'],
        ];

        /**
         * If the user already exists, then log him in. 
         * Update his info, just in case they have been changed since he last logged in.
         */
        if ($user = $this->userRepo->findByFacebookId($userData['id'])) {
            $user->update($data);

            return $user;
        }

        $user = $this->userRepo->create($data);

        return $user;
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