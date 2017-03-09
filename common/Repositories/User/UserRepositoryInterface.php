<?php namespace Common\Repositories\User;

use Common\Models\Bot;
use Common\Models\User;
use Common\Repositories\BaseRepositoryInterface;

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
}
