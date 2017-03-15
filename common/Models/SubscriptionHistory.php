<?php namespace Common\Models;

use Carbon\Carbon;

class SubscriptionHistory extends ArrayModel
{

    protected $dates = ['action_at'];
    
    public $action;
    /** @type Carbon */
    public $action_at;
}