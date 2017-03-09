<?php namespace Common\Models;

/**
 * @property \MongoDB\BSON\ObjectID $message_id
 * @property \MongoDB\BSON\ObjectID $sequence_id
 * @property \MongoDB\BSON\ObjectID $subscriber_id
 */
class SequenceSchedule extends BaseModel
{

    protected $collection = 'sequence_schedule';
    public $dates = ['send_at'];
}
