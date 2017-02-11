<?php namespace App\Models;

use Carbon\Carbon;

class SubscriptionHistory extends ArrayModel
{

    public $action;
    /** @type Carbon */
    public $action_at;

}