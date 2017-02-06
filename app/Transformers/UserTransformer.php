<?php namespace App\Transformers;

use App\Models\User;

class UserTransformer extends BaseTransformer
{

    public function transform(User $user)
    {
        $ret = [
            'id'          => $user->id,
            'facebook_id' => $user->facebook_id,
            'full_name'   => $user->full_name,
            'first_name'  => $user->first_name,
            'last_name'   => $user->last_name,
            'gender'      => $user->gender,
            'avatar_url'  => $user->avatar_url,
            'email'       => $user->email,
        ];
        
        if ($user->jwt_token) {
            $ret['token'] = $user->jwt_token;
        }

        return $ret;
    }
}
