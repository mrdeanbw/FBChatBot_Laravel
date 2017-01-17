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

    /**
     * Sync a user's pages
     * @param User  $user
     * @param array $pages
     * @param bool  $detaching Whether or not to detach the attached tags which are not included in the passed $tags
     */
    public function syncPages(User $user, array $pages, $detaching);

    /**
     * Determine whether or a not a user already manages a page
     * @param User $user
     * @param int  $id Facebook ID of the page
     * @return bool
     */
    public function managesFacebookPage(User $user, $id);

    /**
     * @param int  $id
     * @param Page $page
     * @return User
     */
    public function findForPage($id, Page $page);

    /**
     * Persist the subscriber info, on the user-page relation
     * @param User       $user
     * @param Page       $page
     * @param Subscriber $subscriber
     */
    public function associateWithPageAsSubscriber(User $user, Page $page, Subscriber $subscriber);
}
