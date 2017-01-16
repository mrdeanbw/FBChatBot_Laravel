<?php namespace App\Repositories\User;

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
}
