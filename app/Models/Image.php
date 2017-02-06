<?php namespace App\Models;

class Image extends Message
{

    public static $type = 'image';
    public $image_url;
    /** @type ImageFile */
    public $file;

    /**
     * Text constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->file = new ImageFile(array_pull($data, 'file'));
        parent::__construct($data);
    }
}
