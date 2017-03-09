<?php namespace Common\Models;

use MongoDB\BSON\ObjectID;

/**
 * @property ObjectID $bot_id
 * @property ObjectID $subscriber_id
 * @property ObjectID $message_id
 */
class SentMessage extends BaseModel
{

    public $dates = ['sent_at', 'delivered_at', 'read_at'];
}
