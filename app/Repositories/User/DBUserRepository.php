<?php namespace App\Repositories\User;

use DB;
use App\Models\Bot;
use App\Models\User;
use App\Models\Subscriber;
use App\Repositories\DBBaseRepository;

class DBUserBaseRepository extends DBBaseRepository implements UserRepositoryInterface
{

    /**
     * @return string
     */
    public function model()
    {
        return User::class;
    }

    /**
     * Find a user by Facebook ID.
     * @param $facebookId
     * @return User|null
     */
    public function findByFacebookId($facebookId)
    {
        return User::whereFacebookId($facebookId)->first();
    }

    /**
     * Checks if the user is subscribed to a page
     * @param User $user
     * @param Bot  $page
     * @return bool
     */
    public function isSubscribedToPage(User $user, Bot $page)
    {
        $subQuery = $this->userAsSubscriberSubQuery($user, $page);

        return Subscriber::where('id', $subQuery)->exists();
    }

    /**
     * Return the user's subscriber instance to page.
     * @param User $user
     * @param Bot  $page
     * @return Subscriber
     */
    public function asSubscriber(User $user, Bot $page)
    {
        $subQuery = $this->userAsSubscriberSubQuery($user, $page);

        return Subscriber::where('id', $subQuery)->first();
    }

    /**
     * @param User $user
     * @param Bot  $page
     * @return \Illuminate\Database\Query\Expression
     */
    private function userAsSubscriberSubQuery(User $user, Bot $page)
    {
        $subQuery = DB::raw("(SELECT `subscriber_id` FROM `page_user` WHERE `page_id` = {$page->id} AND `user_id` = {$user->id})");

        return $subQuery;
    }

    /**
     * Sync a user's pages
     * @param User  $user
     * @param array $pages
     * @param bool  $detaching Whether or not to detach the attached tags which are not included in the passed $tags
     */
    public function syncBots(User $user, array $pages, $detaching)
    {
        $user->pages()->sync($pages, $detaching);
    }

    /**
     * Determine whether or a not a user already manages a page
     * @param User $user
     * @param Bot  $bot
     * @return bool
     */
    public function managesBotForFacebookPage(User $user, Bot $bot)
    {
        return in_array($user->id, array_pluck($bot->users, 'user_id'));
    }

    /**
     * @param int $id
     * @param Bot $bot
     * @return User|null
     */
    public function findByIdForBot($id, Bot $bot)
    {
        $userIds = array_column($bot->users, 'user_id');
        if (! in_array($id, $userIds)) {
            return null;
        }

        return User::find($id);
    }

    /**
     * Persist the subscriber info, on the user-page relation
     * @param User       $user
     * @param Bot        $page
     * @param Subscriber $subscriber
     */
    public function associateWithPageAsSubscriber(User $user, Bot $page, Subscriber $subscriber)
    {
        $user->pages()->updateExistingPivot($page->id, ['subscriber_id' => $subscriber->id]);
    }

}
