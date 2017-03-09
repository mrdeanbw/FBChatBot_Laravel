<?php namespace Common\Models;

class Text extends Message
{

    public $type = 'text';
    public $text;
    /** @type Button[] */
    public $buttons;

    /**
     * Text constructor.
     * @param array $data
     * @param bool  $strict
     */
    public function __construct(array $data, $strict = false)
    {
        $this->buttons = [];
        foreach (array_pull($data, 'buttons', []) as $button) {
            $this->buttons[] = new Button($button, $strict);
        }

        parent::__construct($data, $strict);
    }
}
