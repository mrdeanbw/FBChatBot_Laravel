<?php namespace Common\Models;

class InboxMessage extends BaseModel
{
    protected $collection = 'inbox';
    public $dates = ['action_at'];
}
