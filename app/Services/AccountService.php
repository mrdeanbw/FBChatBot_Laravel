<?php

namespace App\Services;

use App\Models\User;

class AccountService
{

    /**
     * @param $facebookUser
     * @return User
     */
    public function createOrGetUser($facebookUser)
    {
        /** @type User $user */
        $user = User::firstOrNew(['facebook_id' => $facebookUser['facebook_id']]);

        $user->full_name = $facebookUser['name'];
        $user->first_name = $facebookUser['first_name'];
        $user->last_name = $facebookUser['last_name'];
        $user->gender = $facebookUser['gender'];
        $user->avatar_url = array_get($facebookUser, 'picture.data.url');
        $user->email = array_get($facebookUser, 'email');
        $user->access_token = $facebookUser['access_token'];
        $user->granted_permissions = $facebookUser['granted_permissions'];
        $user->save();

        return $user;
    }

}