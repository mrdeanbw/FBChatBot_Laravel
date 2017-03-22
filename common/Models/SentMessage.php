<?php namespace Common\Models;

/**
 * @property \MongoDB\BSON\ObjectID $bot_id
 * @property \MongoDB\BSON\ObjectID $subscriber_id
 * @property \MongoDB\BSON\ObjectID $message_id
 */
class SentMessage extends BaseModel
{

    public $dates = ['sent_at', 'delivered_at', 'read_at'];
}
