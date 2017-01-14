<?php

namespace App\Models;


/**
 * App\Models\SequenceMessageSchedule
 *
 * @property integer                                  $sequence_id
 * @property integer                                  $subscriber_id
 * @property \Carbon\Carbon                           $send_at
 * @property string                                   $status
 * @property \Carbon\Carbon                           $created_at
 * @property \Carbon\Carbon                           $updated_at
 * @property-read \App\Models\SequenceMessageSchedule $sequenceMessage
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SequenceMessageSchedule whereSequenceId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SequenceMessageSchedule whereSubscriberId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SequenceMessageSchedule whereSendAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SequenceMessageSchedule whereStatus($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SequenceMessageSchedule whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SequenceMessageSchedule whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 * @property integer                                  $sequence_message_id
 * @property-read \App\Models\Subscriber              $subscriber
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SequenceMessageSchedule whereSequenceMessageId($value)
 * @property integer $id
 * @property string $sent_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SequenceMessageSchedule whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SequenceMessageSchedule whereSentAt($value)
 */
class SequenceMessageSchedule extends BaseModel
{

    protected $guarded = ['id'];

    protected $dates = ['send_at'];

    public function sequence_message()
    {
        return $this->sequenceMessage();
    }

    public function sequenceMessage()
    {
        return $this->belongsTo(SequenceMessage::class);
    }

    public function subscriber()
    {
        return $this->belongsTo(Subscriber::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function (SequenceMessageSchedule $schedule) {
            if (! $schedule->sequence_id) {
                $schedule->sequence_id = $schedule->sequence_message()->withTrashed()->firstOrFail()->sequence->id;
            }
        });
    }

}
