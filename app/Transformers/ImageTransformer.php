<?php namespace App\Transformers;

use Common\Models\Image;
use Common\Models\MessageRevision;

class ImageTransformer extends BaseTransformer
{

    /**
     * @param Image|MessageRevision $image
     * @return array
     */
    public function transform($image)
    {
        return [
            'image_url' => $image->image_url,
        ];
    }

}