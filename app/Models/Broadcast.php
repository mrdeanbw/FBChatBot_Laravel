<?php namespace App\Models;

use App\Services\AudienceService;

/**
 * App\Models\Broadcast
 *
 * @property int                                                                             $id
 * @property string                                                                          $name
 * @property string                                                                          $timezone
 * @property string                                                                          $notification
 * @property \Carbon\Carbon                                                                  $send_at
 * @property string                                                                          $date
 * @property string                                                                          $time
 * @property bool                                                                            $send_from
 * @property bool                                                                            $send_to
 * @property int                                                                             $page_id
 * @property int                                                                             $sent
 * @property int                                                                             $read
 * @property int                                                                             $clicked
 * @property bool                                                                            $filter_enabled
 * @property string                                                                          $filter_type
 * @property string                                                                          $status
 * @property \Carbon\Carbon                                                                  $created_at
 * @property \Carbon\Carbon                                                                  $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\BroadcastSchedule[]   $schedule
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Subscriber[]          $subscribers
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[]        $messageBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[]        $unorderedMessageBlocks
 * @property-read \App\Models\Page                                                           $page
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AudienceFilterGroup[] $filterGroups
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Broadcast whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Broadcast whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Broadcast whereTimezone($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Broadcast whereNotification($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Broadcast whereSendAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Broadcast whereDate($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Broadcast whereTime($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Broadcast whereSendFrom($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Broadcast whereSendTo($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Broadcast wherePageId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Broadcast whereSent($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Broadcast whereRead($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Broadcast whereClicked($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Broadcast whereFilterEnabled($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Broadcast whereFilterType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Broadcast whereStatus($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Broadcast whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Broadcast whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 */
class Broadcast extends BaseModel implements HasMessageBlocksInterface, HasFilterGroupsInterface
{

    use HasMessageBlocks, HasFilterGroups, BelongsToPage;

    protected $guarded = ['id'];

    protected $dates = ['send_at', 'subscribers.read_at'];

    protected $casts = ['subscribers.data' => 'array', 'filter_enabled' => 'boolean'];

    public function schedule()
    {
        return $this->hasMany(BroadcastSchedule::class);
    }

    public function subscribers()
    {
        return $this->belongsToMany(Subscriber::class)->withPivot(['read_at', 'delivered_at', 'clicks', 'sent_at']);
    }

    public function activeTargetAudienceCount()
    {
        /** @type AudienceService $audience */
        $audience = app(AudienceService::class);

        return $audience->activeTargetAudienceCount($this);
    }
}
