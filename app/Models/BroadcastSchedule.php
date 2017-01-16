<?php namespace App\Models;

/**
 * App\Models\BroadcastSchedule
 *
 * @property int                        $id
 * @property int                        $broadcast_id
 * @property float                      $timezone
 * @property \Carbon\Carbon             $send_at
 * @property string                     $status
 * @property \Carbon\Carbon             $created_at
 * @property \Carbon\Carbon             $updated_at
 * @property-read \App\Models\Broadcast $broadcast
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BroadcastSchedule whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BroadcastSchedule whereBroadcastId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BroadcastSchedule whereTimezone($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BroadcastSchedule whereSendAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BroadcastSchedule whereStatus($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BroadcastSchedule whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BroadcastSchedule whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 */
class BroadcastSchedule extends BaseModel
{

    protected $dates = ['send_at'];

    public function broadcast()
    {
        return $this->belongsTo(Broadcast::class);
    }

}
