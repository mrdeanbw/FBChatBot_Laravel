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
//    /**
//     * Persist the subscriber info, on the user-page relation
//     *
//     * @param $userId
//     *
//     * @return String
//     */
//
//    public function generateReferralLink(USer $user);
//
//    /**
//     * Returns a user's referral code - decrypted
//     *
//     * @param User $user
//     *
//     * @return mixed
//     */
//
//    public function getDecryptedCode(User $user);
//
//    /**
//     * Create a referral(child) between for a given user(parent)
//     *
//     * @param User $parent
//     * @param User $child
//     *
//     * @return mixed
//     */
//
//    public function connectReferrals(User $parent, User $child);
//
//    /**
//     * Update a user's amount of credits
//     *
//     * @param $userId
//     * @param $amount
//     *
//     * @return mixed
//     */
//
//    public function addCredits(User $user, $amount);
}
