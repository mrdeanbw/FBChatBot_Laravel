<?php namespace App\Repositories\User;

use App\Models\Bot;
use App\Models\User;
use App\Models\Subscriber;
use App\Repositories\BaseRepositoryInterface;

interface UserRepositoryInterface extends BaseRepositoryInterface
{

    /**
     * Find a user by Facebook ID.
     * @param $facebookId
     * @return User|null
     */
    public function findByFacebookId($facebookId);

    /**
     * Checks if the user is subscribed to a page
     * @param User $user
     * @param Bot  $page
     * @return bool
     */
    public function isSubscribedToPage(User $user, Bot $page);

    /**
     * Return the user's subscriber instance to page.
     * @param User $user
     * @param Bot  $page
     * @return Subscriber
     */
    public function asSubscriber(User $user, Bot $page);

    /**
     * Sync a user's pages
     * @param User  $user
     * @param array $bots
     * @param bool  $detaching Whether or not to detach the attached bots which are not included in the passed $bots
     */
    public function syncBots(User $user, array $bots, $detaching);

    /**
     * Determine whether or a not a user already manages a page
     * @param User $user
     * @param Bot  $bot
     * @return bool
     */
    public function managesBotForFacebookPage(User $user, Bot $bot);

    /**
     * @param int $id
     * @param Bot $page
     * @return User
     */
    public function findByIdForBot($id, Bot $page);

    /**
     * Persist the subscriber info, on the user-page relation
     * @param User       $user
     * @param Bot        $page
     * @param Subscriber $subscriber
     */
    public function associateWithPageAsSubscriber(User $user, Bot $page, Subscriber $subscriber);

    /**
     * Persist the subscriber info, on the user-page relation
     *
     * @param $userId
     *
     * @return String
     */

    public function generateReferralLink(USer $user);

    /**
     * Returns a user's referral code - decrypted
     *
     * @param User $user
     *
     * @return mixed
     */

    public function getDecryptedCode(User $user);

    /**
     * Create a referral(child) between for a given user(parent)
     *
     * @param User $parent
     * @param User $child
     *
     * @return mixed
     */

    public function connectReferrals(User $parent, User $child);

    /**
     * Update a user's amount of credits
     *
     * @param $userId
     * @param $amount
     *
     * @return mixed
     */

    public function addCredits(User $user, $amount);
}
