<?php namespace App\Models;


class SequenceMessage extends ArrayModel
{
    public $id;
    
    public $name;

    public $order;

    /** @type  array */
    public $conditions;
    
    public $template_id;

    public $live;
}
