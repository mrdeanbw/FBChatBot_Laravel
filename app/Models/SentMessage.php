<?php namespace App\Models;

class SentMessage extends BaseModel
{

    public $dates = ['sent_at', 'delivered_at', 'read_at'];
}
