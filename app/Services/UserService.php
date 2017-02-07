<?php namespace App\Services;

use App\Models\Bot;
use App\Models\User;
use App\Repositories\User\UserRepositoryInterface;

class UserService
{

    CONST PAGE_MANAGING_PERMISSIONS = ['manage_pages', 'pages_messaging', 'pages_messaging_subscriptions'];

    /**
     * @type UserRepositoryInterface
     */
    private $userRepo;

    /**
     * UserService constructor.
     * @param UserRepositoryInterface $userRepo
     */
    public function __construct(UserRepositoryInterface $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    /**
     * Whether or not the user has all the sufficient permissions to manage pages.
     * @param User $user
     * @return bool
     */
    public function hasAllManagingPagePermissions(User $user)
    {
        return ! array_diff(self::PAGE_MANAGING_PERMISSIONS, $user->granted_permissions);
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
            $this->userRepo->update($user, $data);

            return $user;
        }

        return $this->userRepo->create($data);
    }

    /**
     * Sync a user's pages
     * @param User  $user
     * @param array $bots
     * @param bool  $detaching Whether or not to detach the attached tags which are not included in the passed $tags
     */
    public function syncBots(User $user, $bots, $detaching)
    {
        return $this->userRepo->syncBots($user, $bots, $detaching);
    }
}