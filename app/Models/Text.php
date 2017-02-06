<?php namespace App\Models;

class Text extends Message
{
    public $type = 'text';
    public $text;
    /** @type Button[] */
    public $buttons;

    /**
     * Text constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->buttons = [];
        foreach (array_pull($data, 'buttons', []) as $button) {
            $this->buttons[] = new Button($button);
        }

        parent::__construct($data);
    }
}
