<?php namespace App\Models;

use MongoDB\BSON\ObjectID;

/**
 * @property Template $template
 */
class Button extends Message
{

    public $type = 'button';
    public $title;
    public $url;
    /** @type Message[] */
    public $messages;
    public $template_id;
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
        if ($templateId = array_get($data, 'template.id')) {
            $this->template_id = $templateId;
            $this->messages = [];
        } else {
            if ($this->messages = array_pull($data, 'messages', [])) {
                $this->messages = array_map(function ($message) use ($strict) {
                    return is_array($message)? Message::factory($message, $strict) : $message;
                }, $this->messages);
            }
        }

        $actions = [];
        foreach (['add_tags', 'remove_tags', 'add_sequences', 'remove_sequences'] as $action) {
            $actions[$action] = array_get($data, "actions.{$action}", []);
        }
        $data['actions'] = $actions;

        parent::__construct($data, $strict);
    }
}
