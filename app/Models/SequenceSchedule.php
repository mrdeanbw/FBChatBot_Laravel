<?php namespace App\Models;

class SequenceSchedule extends BaseModel
{

    protected $collection = 'sequence_schedule';
    public $dates = ['send_at'];
}
