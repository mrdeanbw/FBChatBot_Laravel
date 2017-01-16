<?php namespace App\Repositories\User;

use App\Models\User;
use App\Repositories\BaseEloquentRepository;

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
}
