<?php

namespace App\Models;


/**
 * App\Models\BroadcastSchedule
 *
 * @mixin \Eloquent
 * @property integer                    $id
 * @property integer                    $broadcast_id
 * @property float                      $timezone
 * @property string                     $send_at
 * @property \Carbon\Carbon             $created_at
 * @property \Carbon\Carbon             $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BroadcastSchedule whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BroadcastSchedule whereBroadcastId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BroadcastSchedule whereTimezone($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BroadcastSchedule whereSendAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BroadcastSchedule whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BroadcastSchedule whereUpdatedAt($value)
 * @property string                     $status
 * @property-read \App\Models\Broadcast $broadcast
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BroadcastSchedule whereStatus($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 */
class BroadcastSchedule extends BaseModel
{

    protected $guarded = ['id'];

    protected $dates = ['send_at'];

    public function broadcast()
    {
        return $this->belongsTo(Broadcast::class);
    }

}
