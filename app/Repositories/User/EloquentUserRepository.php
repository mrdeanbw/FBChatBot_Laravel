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
}
