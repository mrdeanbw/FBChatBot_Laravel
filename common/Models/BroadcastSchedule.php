<?php namespace Common\Models;

class BroadcastSchedule extends ArrayModel
{

    protected $dates = ['send_at'];

    /**
     * @type int
     */
    public $utc_offset;

    /**
     * @type int
     */
    public $status;

    /**
     * @type \Carbon\Carbon
     */
    public $send_at;
}
