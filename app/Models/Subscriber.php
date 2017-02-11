<?php namespace App\Models;

/**
 * Class Subscriber
 * @property bool           $active
 * @property string         $facebook_id
 * @property \Carbon\Carbon $last_subscribed_at
 * @property \Carbon\Carbon $last_unsubscribed_at
 * @property string         $first_name
 * @property string         $last_name
 * @property string         $full_name
 * @property string         $avatar_url
 * @property string         $gender
 * @property \Carbon\Carbon $last_contacted_at
 * @property array          $sequence
 * @property array          $tags
 * @property array          $removed_sequences
 */
class Subscriber extends BaseModel
{

    public $dates = ['last_contacted_at', 'last_subscribed_at', 'last_unsubscribed_at'];

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

}