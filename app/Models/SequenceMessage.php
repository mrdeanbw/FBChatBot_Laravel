<?php namespace App\Models;

/**
 * @property Template $template
 */
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
