<?php namespace App\Models;

/**
 * Class Subscriber
 *
 * @property bool           $active
 * @property string         $facebook_id
 * @property \Carbon\Carbon $last_subscribed_at
 * @property \Carbon\Carbon $last_unsubscribed_at
 * @property string         $first_name
 * @property string         $last_name
 * @property string         $full_name
 * @property string         $avatar_url
 * @property string         $gender
 * @property \Carbon\Carbon $last_interaction_at
 * @property array          $sequences
 * @property array          $tags
 * @property array          $removed_sequences
 * @property double         $timezone
 * @property double         $locale
 * @property mixed          $bot_id
 */
class Subscriber extends BaseModel
{

    use HasEmbeddedArrayModels;

    protected $multiArrayModels = ['history' => SubscriptionHistory::class];

    public $dates = ['last_interaction_at', 'last_subscribed_at', 'last_unsubscribed_at'];

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}