<?php namespace App\Models;

class Image extends Message
{

    public $image_url;
    /** @type ImageFile */
    public $file;

    /**
     * Text constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        if ($file = array_pull($data, 'file')) {
            $this->file = new ImageFile($file);
        }
        parent::__construct($data);
    }
}
