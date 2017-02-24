<?php namespace App\Transformers;

use App\Models\Image;
use App\Models\MessageRevision;

class ImageTransformer extends BaseTransformer
{

    /**
     * @param Image|MessageRevision $image
     * @return array
     */
    public function transform($image)
    {
        return [
            'id'        => $image->id->__toString(),
            'type'      => $image->type,
            'image_url' => $image->image_url,
            'readonly'  => $image->readonly,
        ];
    }

}