<?php namespace Common\Repositories\User;

use Common\Models\Bot;
use Common\Models\User;
use Common\Repositories\DBBaseRepository;

class DBUserRepository extends DBBaseRepository implements UserRepositoryInterface
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
}
