<?php namespace App\Models;

class MainMenu extends ArrayModel
{

    /** @type Button[] */
    public $buttons = [];

    public function __construct(array $data, $strict = false)
    {
        $this->buttons = [];
        foreach (array_pull($data, 'buttons', []) as $button) {
            $this->buttons[] = new Button($button, $strict);
        }

        parent::__construct($data, $strict);
    }

}
