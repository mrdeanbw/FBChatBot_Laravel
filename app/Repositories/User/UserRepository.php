<?php namespace App\Repositories\User;

use App\Models\Page;
use App\Models\Subscriber;
use App\Models\User;

interface UserRepository
{

    /**
     * Create a new user.
     * @param array $data
     * @return User
     */
    public function create(array $data);

    /**
     * Find a user by Facebook ID.
     * @param $facebookId
     * @return User|null
     */
    public function findByFacebookId($facebookId);

    /**
     * Checks if the user is subscribed to a page
     * @param User $user
     * @param Page $page
     * @return bool
     */
    public function isSubscribedToPage(User $user, Page $page);

    /**
     * Return the user's subscriber instance to page.
     * @param User $user
     * @param Page $page
     * @return Subscriber
     */
    public function asSubscriber(User $user, Page $page);
}
