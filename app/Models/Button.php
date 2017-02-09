<?php namespace App\Models;

use MongoDB\BSON\ObjectID;

class Button extends Message
{

    public $type = 'button';
    public $title;
    public $url;
    /** @type Template */
    public $template;
    public $actions = [
        'add_tags'         => [],
        'remove_tags'      => [],
        'add_sequences'    => [],
        'remove_sequences' => [],
    ];

    /**
     * Button constructor.
     * @param array $data
     * @param bool  $strict
     */
    public function __construct(array $data, $strict = false)
    {
        if ($template = array_pull($data, 'template', [])) {

            $clean = [
                'explicit' => (bool)array_get($template, 'explicit')
            ];

            if ($clean['explicit']) {
                $clean['id'] = new ObjectID($template['id']);
            } else {
                $clean['messages'] = array_map(function ($message) {
                    return is_array($message)? Message::factory($message, true) : $message;
                }, $template['messages']);
            }

            $this->template = $clean;
        }

        parent::__construct($data, $strict);
    }
}
