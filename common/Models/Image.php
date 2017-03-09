<?php namespace Common\Models;

class Image extends Message
{

    public $image_url;
    /** @type ImageFile */
    public $file;

    /**
     * Text constructor.
     * @param array $data
     * @param bool  $strict
     */
    public function __construct(array $data, $strict = false)
    {
        if ($file = array_pull($data, 'file')) {
            $this->file = new ImageFile($file, $strict);
        }
        parent::__construct($data, $strict);
    }
}
