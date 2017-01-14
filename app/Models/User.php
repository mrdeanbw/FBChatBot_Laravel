<?php

namespace App\Models;

use DB;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * App\Models\User
 *
 * @property integer                                                          $id
 * @property string                                                           $facebook_id
 * @property string                                                           $first_name
 * @property string                                                           $last_name
 * @property string                                                           $full_name
 * @property string                                                           $email
 * @property string                                                           $gender
 * @property string                                                           $avatar_url
 * @property string                                                           $access_token
 * @property string                                                           $granted_permissions
 * @property \Carbon\Carbon                                                   $created_at
 * @property \Carbon\Carbon                                                   $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Page[] $pages
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereFacebookId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereFirstName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereLastName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereFullName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereEmail($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereGender($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereAvatarUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereAccessToken($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereGrantedPermissions($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 */
class User extends BaseModel implements AuthenticatableContract, AuthorizableContract, JWTSubject
{

    use Authenticatable, Authorizable;

    protected $guarded = ['id'];

    protected $casts = [
        'granted_permissions' => 'array'
    ];


    public function hasManagingPagePermissions()
    {
        $requiredPermissions = ['manage_pages', 'pages_messaging', 'pages_messaging_subscriptions'];

        return ! array_diff($requiredPermissions, $this->granted_permissions);
    }

    public function pages()
    {
        return $this->belongsToMany(Page::class)->withPivot('subscriber_id');
    }

    /**
     * @param int|Page $pageId
     * @return Subscriber|null
     */
    public function subscriber($pageId)
    {
        if (is_a($pageId, Page::class)) {
            $pageId = $pageId->id;
        }

        return Subscriber::where('id', DB::raw("(SELECT `subscriber_id` FROM `page_user` WHERE `page_id` = {$pageId} AND `user_id` = {$this->attributes['id']})"))->first();
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->attributes['id'];
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
}
