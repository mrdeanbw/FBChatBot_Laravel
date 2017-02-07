<?php namespace App\Transformers;

use App\Models\Image;

class ImageTransformer extends BaseTransformer
{

    public function transform(Image $image)
    {
        return [
            'id'        => $image->id,
            'type'      => $image->type,
            'image_url' => $image->image_url,
            'readonly'  => $image->readonly,
        ];
    }

}