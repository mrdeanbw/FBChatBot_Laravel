<?php namespace App\Models;

use Carbon\Carbon;

class SubscriptionHistory extends ArrayModel
{

    public $action;
    /** @type Carbon */
    public $action_at;

    public function __construct(array $data, $strict = false)
    {
        $data['action_at'] = carbon_date($data['action_at']);
        parent::__construct($data, $strict);
    }
}