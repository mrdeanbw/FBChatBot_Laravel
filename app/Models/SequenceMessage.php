<?php namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\SequenceMessage
 *
 * @property int                                                                                 $id
 * @property int                                                                                 $sequence_id
 * @property int                                                                                 $order
 * @property string                                                                              $name
 * @property int                                                                                 $days
 * @property bool                                                                                $is_live
 * @property \Carbon\Carbon                                                                      $deleted_at
 * @property \Carbon\Carbon                                                                      $created_at
 * @property \Carbon\Carbon                                                                      $updated_at
 * @property-read \App\Models\Sequence                                                           $sequence
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Subscriber[]              $subscribers
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SequenceMessageSchedule[] $schedules
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[]            $messageBlocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MessageBlock[]            $unorderedMessageBlocks
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SequenceMessage whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SequenceMessage whereSequenceId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SequenceMessage whereOrder($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SequenceMessage whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SequenceMessage whereDays($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SequenceMessage whereIsLive($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SequenceMessage whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SequenceMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SequenceMessage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\BaseModel date($columnName, $value)
 * @mixin \Eloquent
 */
class SequenceMessage extends BaseModel implements HasMessageBlocksInterface
{

    use HasMessageBlocks, SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $casts = ['is_live' => 'boolean'];

    public function sequence()
    {
        return $this->belongsTo(Sequence::class);
    }

    public function page()
    {
        return $this->sequence->page();
    }

    public function subscribers()
    {
        return $this->belongsToMany(Subscriber::class)->withPivot('status', 'send_at');
    }

    /**
     * @return SequenceMessage|null
     */
    public function next()
    {
        return $this->sequence->messages()->where('order', '>', $this->attributes['order'])->first();
    }

    public function previous()
    {
        return $this->sequence->messages()->where('order', '<', $this->attributes['order'])->first();
    }

    public function schedules()
    {
        return $this->hasMany(SequenceMessageSchedule::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleted(function (SequenceMessage $message) {
            $order = 1;
            foreach (Sequence::findOrFail($message->sequence_id)->messages as $message) {
                $message->order = $order++;
                $message->save();
            }
        });

        static::updating(function (SequenceMessage $message) {

            if ($message->is_live != $message->fresh()->is_live) {

                if ($message->is_live) {
                    //                    static::$dispatcher->fire(new SequenceMessageIsLive($message));

                    return;
                }

                //                static::$dispatcher->fire(new SequenceMessageIsDraft($message));
            }
        });
    }


}
