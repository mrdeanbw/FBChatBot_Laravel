<?php namespace Common\Models;

use Carbon\Carbon;

class SubscriptionHistory extends ArrayModel
{

    public $action;
    /** @type Carbon */
    public $action_at;

    public function isDate($key)
    {
        return $key == 'action_at';
    }
}