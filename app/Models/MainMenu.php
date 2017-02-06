<?php namespace App\Models;

class MainMenu extends ArrayModel
{

    /** @type Button[] */
    public $buttons = [];

    public function __construct(array $data)
    {
        $this->buttons = [];
        foreach (array_pull($data, 'buttons', []) as $button) {
            $this->buttons[] = new Button($button);
        }

        parent::__construct($data);
    }

}
