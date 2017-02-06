<?php namespace App\Models;

class Card extends Message
{
    public $type = 'card';
    public $title;
    public $subtitle;
    public $url;
    public $image_url;
    /** @type Button[] */
    public $buttons;
    /** @type ImageFile */
    public $file;

    /**
     * Card constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->buttons = [];

        foreach (array_pull($data, 'buttons', []) as $button) {
            $this->buttons[] = new Button($button);
        }

        if ($file = array_pull($data, 'file')) {
            $this->file = new ImageFile($file);
        }

        parent::__construct($data);
    }
}
