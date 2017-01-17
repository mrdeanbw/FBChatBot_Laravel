<?php namespace App\Repositories\User;

use App\Models\User;
use App\Models\Page;
use App\Models\Subscriber;
use App\Repositories\BaseEloquentRepository;
use DB;

class EloquentUserRepository extends BaseEloquentRepository implements UserRepository
{

    /**
     * Create a new user.
     * @param array $data
     * @return User
     */
    public function create(array $data)
    {
        return User::create($data);
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
     * @param Page $page
     * @return bool
     */
    public function isSubscribedToPage(User $user, Page $page)
    {
        $subQuery = $this->userAsSubscriberSubQuery($user, $page);

        return Subscriber::where('id', $subQuery)->exists();
    }

    /**
     * Return the user's subscriber instance to page.
     * @param User $user
     * @param Page $page
     * @return Subscriber
     */
    public function asSubscriber(User $user, Page $page)
    {
        $subQuery = $this->userAsSubscriberSubQuery($user, $page);

        return Subscriber::where('id', $subQuery)->first();
    }

    /**
     * @param User $user
     * @param Page $page
     * @return \Illuminate\Database\Query\Expression
     */
    private function userAsSubscriberSubQuery(User $user, Page $page)
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
    public function syncPages(User $user, array $pages, $detaching)
    {
        $user->pages()->sync($pages, $detaching);
    }

    /**
     * Determine whether or a not a user already manages a page
     * @param User $user
     * @param int  $id Facebook ID of the page
     * @return bool
     */
    public function managesFacebookPage(User $user, $id)
    {
        return $user->pages()->whereFacebookId($id)->exists();
    }

    /**
     * @param int  $id
     * @param Page $page
     * @return User
     */
    public function findForPage($id, Page $page)
    {
        return $page->users()->find($id);
    }

    /**
     * Persist the subscriber info, on the user-page relation
     * @param User       $user
     * @param Page       $page
     * @param Subscriber $subscriber
     */
    public function associateWithPageAsSubscriber(User $user, Page $page, Subscriber $subscriber)
    {
        $user->pages()->updateExistingPivot($page->id, ['subscriber_id' => $subscriber->id]);
    }
}
