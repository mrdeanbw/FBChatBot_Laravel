<?php namespace App\Models;

class Button extends Message
{

    public $type = 'button';
    public $title;
    public $url;
    public $actions = [
        'add_tags'         => [],
        'remove_tags'      => [],
        'add_sequences'    => [],
        'remove_sequences' => [],
    ];
}
