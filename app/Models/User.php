<?php namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use App\Repositories\User\UserRepositoryInterface;

/**
 * @property string $jwt_token
 * @property string $access_token
 * @property array $granted_permissions
 */
class User extends BaseModel implements AuthenticatableContract, JWTSubject
{

    use Authenticatable;

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->id;
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /*
     * Get the user's referrals
     */

    public function referrals()
    {
        return $this->belongsToMany('App\User');
    }

    /*
     * Get the referred user's parent referral
     */

    public function parentReferral()
    {
        return $this->belongsTo('App\User');
    }

    /*
     * Get the amount of users the person has referred
     */

    public function countReferrals()
    {
        return User::withCount('referrals')->get();
    }

    /*
     * Generate a referral link for new users
     */

    public static function boot()
    {
        static::created(function(User $user)
        {
            $user->userRepository->generateReferralLink($user->id);
        });

        parent::boot();
    }
}
