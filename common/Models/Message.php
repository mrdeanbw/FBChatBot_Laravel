<?php namespace Common\Models;

/**
 * Class Message
 * @package Common\Models
 * @property \MongoDB\BSON\ObjectID last_revision_id
 * @property \MongoDB\BSON\ObjectID id
 * @property string                 type
 * @property bool                   readonly
 * @property array|null             stats
 */
abstract class Message extends ArrayModel
{

    /**
     * @param array $message
     * @return Message
     */
    public static function factory($message)
    {
        /** @type Message $model */
        $model = "Common\\Models\\" . studly_case($message['type']);

        return new $model($message);
    }
}
