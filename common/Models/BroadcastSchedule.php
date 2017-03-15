<?php namespace Common\Models;

class BroadcastSchedule extends ArrayModel
{

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

    public function isDate($key)
    {
        return $key == 'send_at';
    }
}
