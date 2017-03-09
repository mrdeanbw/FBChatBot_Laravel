<?php namespace Common\Models;

/**
 * @property array $stats
 */
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
     * @param bool  $strict
     */
    public function __construct(array $data, $strict = false)
    {
        $this->buttons = [];

        foreach (array_pull($data, 'buttons', []) as $button) {
            $this->buttons[] = new Button($button, $strict);
        }

        if ($file = array_pull($data, 'file')) {
            $this->file = new ImageFile($file, $strict);
        }

        parent::__construct($data, $strict);
    }
}
