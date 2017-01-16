<?php namespace App\Models;

/**
 * App\Models\SubscriptionHistory
 *
 * @property int $id
 * @property int $subscriber_id
 * @property int $page_id
 * @property string $action
 * @property \Carbon\Carbon $action_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\Subscriber $subscriber
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SubscriptionHistory whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SubscriptionHistory whereSubscriberId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SubscriptionHistory wherePageId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SubscriptionHistory whereAction($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SubscriptionHistory whereActionAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SubscriptionHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SubscriptionHistory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 */
class SubscriptionHistory extends BaseModel
{
    public $table = 'subscription_history';

    protected $dates = ['action_at'];

    public function subscriber()
    {
        return $this->belongsTo(Subscriber::class);
    }
}
