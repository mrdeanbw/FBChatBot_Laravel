<?php

namespace App\Models;

use App\Events\Resubscription;
use App\Events\Unsubscription;
use App\Services\SequenceService;
use Carbon\Carbon;

/**
 * App\Models\Subscriber
 *
 * @property integer                                                                         $id
 * @property string                                                                          $facebook_id
 * @property string                                                                          $page_id
 * @property string                                                                          $first_name
 * @property string                                                                          $last_name
 * @property string                                                                          $avatar_url
 * @property string                                                                          $locale
 * @property float                                                                           $timezone
 * @property string                                                                          $gender
 * @property \Carbon\Carbon                                                                  $last_contacted_at
 * @property boolean                                                                         $is_active
 * @property \Carbon\Carbon                                                                  $last_subscribed_at
 * @property \Carbon\Carbon                                                                  $last_unsubscribed_at
 * @property \Carbon\Carbon                                                                  $created_at
 * @property \Carbon\Carbon                                                                  $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[]                 $tags
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Sequence[]            $sequences
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Broadcast[]           $broadcasts
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SubscriptionHistory[] $subscriptionHistory
 * @property-read \App\Models\Page                                                           $page
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Subscriber whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Subscriber whereFacebookId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Subscriber wherePageId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Subscriber whereFirstName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Subscriber whereLastName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Subscriber whereAvatarUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Subscriber whereLocale($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Subscriber whereTimezone($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Subscriber whereGender($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Subscriber whereLastContactedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Subscriber whereIsActive($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Subscriber whereLastSubscribedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Subscriber whereLastUnsubscribedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Subscriber whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Subscriber whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SequenceMessageSchedule[] $sequenceSchedules
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageInstance[] $messageInstances
 * @property-read mixed $full_name
 */
class Subscriber extends BaseModel
{

    use BelongsToPage;

    protected $guarded = ['id'];

    protected $dates = ['last_subscribed_at', 'last_unsubscribed_at', 'last_contacted_at'];

    protected $casts = ['is_active' => 'boolean'];

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function sequences()
    {
        return $this->belongsToMany(Sequence::class);
    }

    public function broadcasts()
    {
        return $this->belongsToMany(Broadcast::class);
    }

    public function subscriptionHistory()
    {
        return $this->hasMany(SubscriptionHistory::class)->latest('action_at');
    }

    public function subscription_history()
    {
        return $this->subscriptionHistory();
    }

    public function sequenceSchedules()
    {
        return $this->hasMany(SequenceMessageSchedule::class);
    }
    
    
    public function messageInstances()
    {
        return $this->hasMany(MessageInstance::class);
    }
    
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * @param bool $updating
     * @return SubscriptionHistory
     */
    private function createSubscriptionHistoryRecord($updating = true)
    {
        $record = new SubscriptionHistory();
        $record->action_at = Carbon::now();
        $record->page_id = $this->page_id;
        if ($this->is_active) {
            $record->action = 'subscribed';
            $this->last_subscribed_at = $record->action_at;
            if ($updating) {
                static::$dispatcher->fire(new Resubscription($this));
            } else {
                $this->reSyncSequences();
            }
        } else {
            $record->action = 'unsubscribed';
            $this->last_unsubscribed_at = $record->action_at;
            static::$dispatcher->fire(new Unsubscription($this));
        }
        $this->subscriptionHistory()->save($record);

        return $record;
    }

    public static function boot()
    {
        static::updating(function (Subscriber $subscriber) {
            if ($subscriber->is_active != $subscriber->fresh()->is_active) {
                $subscriber->createSubscriptionHistoryRecord();
            }
        });

        static::created(function (Subscriber $subscriber) {
            if ($subscriber->is_active) {
                $subscriber->createSubscriptionHistoryRecord(false);
            }
        });
    }

    public function syncTags($ids, $detaching = true)
    {
        $this->tags()->sync($ids, $detaching);
        $this->reSyncSequences();
    }

    public function attachTags($id, array $attributes = [], $touch = true)
    {
        $this->tags()->attach($id, $attributes, $touch);
        $this->reSyncSequences();
    }

    public function detachTags($id, $touch = true)
    {
        $this->tags()->detach($id, $touch);
        $this->reSyncSequences();
    }

    private function reSyncSequences()
    {
        /** @type SequenceService $sequenceService */
        $sequenceService = app(SequenceService::class);
        $sequenceService->reSyncSequences($this);
    }

}
