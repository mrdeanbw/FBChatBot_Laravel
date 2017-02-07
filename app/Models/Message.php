<?php namespace App\Models;

abstract class Message extends ArrayModel
{

    public $type;
    public $id;
    public $readonly;

    /**
     * @param array $message
     * @param bool  $strict
     * @return Message
     */
    public static function factory($message, $strict = false)
    {
        /** @type Message $model */
        $model = "App\\Models\\" . studly_case($message['type']);

        return new $model($message, $strict);
    }
}
