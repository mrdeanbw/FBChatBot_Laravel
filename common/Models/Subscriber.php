<?php namespace Common\Models;

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
 * @property array          $history
 */
class Subscriber extends BaseModel
{

    use HasEmbeddedArrayModels;

    public $dates = ['last_interaction_at', 'last_subscribed_at', 'last_unsubscribed_at'];

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * @param array $attributes
     * @param bool  $sync
     * @return BaseModel
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        if ($history = array_get($attributes, 'history')) {
            $attributes['history'] = array_map(function ($record) {
                return new SubscriptionHistory($record);
            }, $history);
        }

        return parent::setRawAttributes($attributes, $sync);
    }
}