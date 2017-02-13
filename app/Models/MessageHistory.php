<?php namespace App\Models;

class MessageHistory extends BaseModel
{

    protected $collection = 'message_history';

    public $dates = ['sent_at', 'delivered_at', 'read_at'];
}
